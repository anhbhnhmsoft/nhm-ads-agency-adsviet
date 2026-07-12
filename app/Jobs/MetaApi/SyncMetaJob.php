<?php

namespace App\Jobs\MetaApi;

use App\Common\Constants\QueueKey\QueueKey;
use App\Models\ServiceUser;
use App\Service\MetaService;
use App\Service\MetaAdsNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Service\TelegramService;
use App\Common\Constants\User\UserRole;
use App\Models\User;
use App\Core\Logging;
use Illuminate\Support\Facades\Cache;

/**
 * Job phục vụ đồng bộ giữ liệu của 1 BM từ Meta API
 *
 * Phase 2: Chỉ sync account list + amount_spent (2 API calls).
 * Bỏ insights polling (đã dùng amount_spent cho billing).
 * Insights + campaigns chỉ sync khi admin bấm "Cập nhật dữ liệu Meta".
 */
class SyncMetaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;
    public int $tries = 1;

    public function __construct(
        protected ServiceUser $serviceUser,
        protected bool $syncAccounts = true,
    )
    {
        $this->onQueue(QueueKey::META_API);
    }

    public function handle(
        MetaService $metaService,
        MetaAdsNotificationService $metaAdsNotificationService,
        TelegramService $telegramService,
    ): void
    {
        if (Cache::has('meta_api_rate_limited_cooldown')) {
            Logging::web("SyncMetaJob: Cooldown rate limit. Bỏ qua SU ID {$this->serviceUser->id}.");
            return;
        }

        try {
            if ($this->syncAccounts) {
                // Chỉ sync account list + amount_spent (2 API calls/SU)
                $syncResult = $metaService->syncMetaAccounts($this->serviceUser);
                if ($syncResult->isError()) {
                    if (self::isRateLimitMessage($syncResult->getMessage())) {
                        $this->handleRateLimit($telegramService, $syncResult->getMessage());
                    }
                    return;
                }
            } else {
                $metaService->setupSettingContextForServiceUser($this->serviceUser);
            }

            // Phase 2: KHÔNG gọi syncMetaAdsAndCampaigns ở đây nữa
            // Insights/campaigns chỉ sync khi admin bấm "Cập nhật dữ liệu Meta" thủ công
        } catch (\Throwable $e) {
            if (self::isRateLimitException($e)) {
                $this->handleRateLimit($telegramService, $e->getMessage());
            }
            throw $e;
        }
    }

    public static function isRateLimitMessage(string $msg): bool
    {
        $msg = strtolower($msg);
        return str_contains($msg, 'rate limit')
            || str_contains($msg, 'request limit')
            || str_contains($msg, 'too many calls')
            || str_contains($msg, '613')
            || str_contains($msg, '80004')
            || str_contains($msg, 'code: 17')
            || str_contains($msg, 'error 17')
            || str_contains($msg, '429');
    }

    public static function isRateLimitException(\Throwable $e): bool
    {
        return self::isRateLimitMessage($e->getMessage());
    }

    private function handleRateLimit(TelegramService $telegramService, string $errorMessage): void
    {
        // Set cooldown 30 phút
        Cache::put('meta_api_rate_limited_cooldown', true, now()->addMinutes(30));

        // Tránh gửi trùng lặp tin nhắn telegram (lock 10 phút)
        if (Cache::has('meta_api_rate_limited_notified_lock')) {
            return;
        }
        Cache::put('meta_api_rate_limited_notified_lock', true, now()->addMinutes(10));

        $message = sprintf(
            "⚠️ <b>[CẢNH BÁO RATE LIMIT META]</b>\n" .
            "Hệ thống đã chạm ngưỡng giới hạn tần suất gọi API (Rate Limit) từ Meta.\n" .
            "<b>Chi tiết lỗi:</b> <code>%s</code>\n\n" .
            "<b>Hành động tự động:</b>\n" .
            "- Tự động tạm dừng (cooldown) các tác vụ đồng bộ API Meta trong <b>30 phút</b>.\n" .
            "- Hệ thống sẽ tự động phục hồi sau 30 phút mà không cần admin thao tác thủ công (không cần gõ stop/start worker).",
            htmlspecialchars($errorMessage)
        );

        // Gửi group hỗ trợ
        $groupId = config('services.telegram.support_group_id');
        if (!empty($groupId)) {
            $telegramService->sendNotification($groupId, $message);
        }

        // Gửi danh sách admin
        try {
            $admins = User::query()
                ->where('role', UserRole::ADMIN->value)
                ->whereNotNull('telegram_id')
                ->get();

            foreach ($admins as $admin) {
                $telegramService->sendNotification($admin->telegram_id, $message);
            }
        } catch (\Throwable $e) {
            Logging::error("Failed to notify admins of Meta Rate Limit: " . $e->getMessage());
        }
    }
}
