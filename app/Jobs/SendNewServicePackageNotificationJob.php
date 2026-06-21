<?php

namespace App\Jobs;

use App\Common\Constants\NotificationType\NotificationType;
use App\Models\ServicePackage;
use App\Models\User;
use App\Service\NotificationService;
use App\Service\UserAlertService;
use App\Core\Logging;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNewServicePackageNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $packageId,
        protected array $allowedUserIds = []
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(
        NotificationService $notificationService,
        UserAlertService $userAlertService
    ): void {
        try {
            $package = ServicePackage::find($this->packageId);
            if (!$package || $package->disabled) {
                Logging::web("SendNewServicePackageNotificationJob: Package not found or disabled. ID: {$this->packageId}");
                return;
            }

            // Get users to notify (CUSTOMER and AGENCY)
            $query = User::query()
                ->whereIn('role', [
                    \App\Common\Constants\User\UserRole::CUSTOMER->value,
                    \App\Common\Constants\User\UserRole::AGENCY->value,
                ])
                ->where('disabled', false);

            if (!empty($this->allowedUserIds)) {
                $query->whereIn('id', $this->allowedUserIds);
            }

            $query->chunk(100, function ($users) use ($package, $notificationService, $userAlertService) {
                foreach ($users as $user) {
                    try {
                        // 1. Create database notification
                        $title = __('services.notifications.new_package_title', ['name' => $package->name]);
                        $description = __('services.notifications.new_package_description', ['name' => $package->name]);

                        $notificationService->send(
                            userId: (int) $user->id,
                            title: $title,
                            description: $description,
                            data: [
                                'package_id' => $package->id,
                                'platform' => $package->platform,
                                'payment_type' => $package->payment_type,
                            ],
                            type: NotificationType::SERVICE_ANNOUNCEMENT->value
                        );

                        // 2. Send Telegram notification (via UserAlertService)
                        $platformLabel = 'N/A';
                        $platformEnum = \App\Common\Constants\Platform\PlatformType::tryFrom((int)$package->platform);
                        if ($platformEnum) {
                            $platformLabel = $platformEnum->label();
                        }

                        $telegramMessage = sprintf(
                            "🔔 <b>%s</b>\n\n" .
                            "📦 <b>%s:</b> %s\n" .
                            "💻 <b>%s:</b> %s\n" .
                            "📝 <b>%s:</b> %s\n\n" .
                            "👉 %s",
                            __('services.notifications.new_package_title', ['name' => $package->name]),
                            __('Gói'),
                            $package->name,
                            __('Nền tảng'),
                            $platformLabel,
                            __('Mô tả'),
                            strip_tags((string)$package->description),
                            __('Đăng ký ngay tại hệ thống!')
                        );

                        $userAlertService->sendPlainText($user, $telegramMessage);

                    } catch (\Throwable $e) {
                        Logging::error(
                            message: "SendNewServicePackageNotificationJob: Error notifying user {$user->id}: " . $e->getMessage(),
                            exception: $e
                        );
                    }
                }
            });

            Logging::web("SendNewServicePackageNotificationJob completed for package ID: {$this->packageId}");
        } catch (\Throwable $e) {
            Logging::error(
                message: 'SendNewServicePackageNotificationJob failed: ' . $e->getMessage(),
                exception: $e
            );
        }
    }
}
