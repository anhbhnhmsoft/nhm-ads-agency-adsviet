<?php

namespace App\Http\Controllers;

use App\Core\Logging;
use App\Service\MetaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class MetaWebhookController extends Controller
{
    private const WEBHOOK_SECRET = 'meta_webhook_verify_token';

    public function __construct(
        protected MetaService $metaService,
    ) {
    }

    /**
     * Handle Meta webhook — verification + events
     *
     * Meta sends:
     * - GET with hub.mode=subscribe & hub.challenge for verification
     * - POST with JSON payload for events
     */
    public function handle(Request $request): JsonResponse
    {
        // ── Verification handshake (GET) ──
        if ($request->isMethod('GET') || $request->input('hub_mode') === 'subscribe') {
            return $this->verify($request);
        }

        // ── Event delivery (POST) ──
        return $this->processEvent($request);
    }

    /**
     * Meta webhook verification
     * GET /webhooks/meta?hub.mode=subscribe&hub.challenge=xxx&hub.verify_token=xxx
     */
    private function verify(Request $request): JsonResponse
    {
        $mode = $request->input('hub_mode');
        $token = $request->input('hub.verify_token');
        $challenge = $request->input('hub.challenge');

        $expectedToken = config('services.meta_webhook.verify_token', env('META_WEBHOOK_VERIFY_TOKEN', 'adviet_meta_webhook_2026'));

        if ($mode === 'subscribe' && $token === $expectedToken && $challenge) {
            Logging::web('MetaWebhook: Verification successful');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Logging::error('MetaWebhook: Verification failed', [
            'mode' => $mode,
            'token_received' => $token ? '***' : null,
        ]);

        return response()->json(['error' => 'Verification failed'], 403);
    }

    /**
     * Process Meta webhook events
     */
    private function processEvent(Request $request): JsonResponse
    {
        $payload = $request->all();
        $object = $payload['object'] ?? null;

        // Log mọi webhook nhận được
        Logging::web('MetaWebhook: Event received', [
            'object' => $object,
            'entry_count' => count($payload['entry'] ?? []),
        ]);

        if ($object !== 'ad_account') {
            return response()->json(['status' => 'ignored', 'object' => $object]);
        }

        $entries = $payload['entry'] ?? [];
        foreach ($entries as $entry) {
            $this->processEntry($entry);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Process a single webhook entry
     */
    private function processEntry(array $entry): void
    {
        $accountId = $entry['id'] ?? null;
        $changes = $entry['changes'] ?? [];

        if (!$accountId) return;

        foreach ($changes as $change) {
            $field = $change['field'] ?? null;
            $value = $change['value'] ?? null;

            Logging::web('MetaWebhook: Change received', [
                'account_id' => $accountId,
                'field' => $field,
                'value' => is_array($value) ? json_encode($value) : $value,
            ]);

            match ($field) {
                'ad_status' => $this->handleAccountStatusChange($accountId, $value),
                'spend_cap' => $this->handleSpendCapChange($accountId, $value),
                default => Logging::web("MetaWebhook: Unhandled field: {$field}", ['account_id' => $accountId]),
            };
        }
    }

    /**
     * Handle account status change (disabled/enabled)
     */
    private function handleAccountStatusChange(string $accountId, $value): void
    {
        $status = $value['ad_status'] ?? $value['status'] ?? null;
        $accountStatus = $value['account_status'] ?? null;

        // Account bị disable
        if ($status === 'INACTIVE' || $accountStatus == 2 || $status === 'disabled') {
            Logging::web("MetaWebhook: Account DISABLED", ['account_id' => $accountId]);

            // Pause tất cả campaigns của account này
            $this->pauseAccountCampaigns($accountId);

            // Gửi Telegram alert
            $this->sendAlert("🔴 Account {$accountId} bị vô hiệu hóa từ Meta webhook. Campaigns đã tự pause.");
        }

        // Account được enable lại
        if ($status === 'ACTIVE' || $accountStatus == 1) {
            Logging::web("MetaWebhook: Account ENABLED", ['account_id' => $accountId]);
            $this->sendAlert("🟢 Account {$accountId} đã được kích hoạt lại từ Meta webhook.");
        }
    }

    /**
     * Handle spend cap reached
     */
    private function handleSpendCapChange(string $accountId, $value): void
    {
        Logging::web("MetaWebhook: Spend cap change", [
            'account_id' => $accountId,
            'value' => $value,
        ]);

        $this->sendAlert("⚠️ Account {$accountId} đã đạt giới hạn chi tiêu (spend cap reached).");
    }

    /**
     * Pause all campaigns for a specific ad account
     */
    private function pauseAccountCampaigns(string $accountId): void
    {
        try {
            $normalizedAccountId = preg_replace('/[^0-9]/', '', $accountId);
            $metaAccount = \App\Models\MetaAccount::query()
                ->where('account_id', $normalizedAccountId)
                ->first();

            if (!$metaAccount || !$metaAccount->service_user_id) {
                Logging::web("MetaWebhook: Account {$accountId} not found or not linked");
                return;
            }

            $campaigns = \App\Models\MetaAdsCampaign::query()
                ->where('meta_account_id', $metaAccount->id)
                ->where('status', '!=', 'PAUSED')
                ->where('status', '!=', 'DELETED')
                ->get(['id']);

            foreach ($campaigns as $campaign) {
                $result = $this->metaService->updateCampaignStatus(
                    (string) $metaAccount->service_user_id,
                    (string) $campaign->id,
                    'PAUSED'
                );
                if ($result->isError()) {
                    Logging::error("MetaWebhook: Failed to pause campaign {$campaign->id}: " . $result->getMessage());
                }
            }

            Logging::web("MetaWebhook: Paused " . count($campaigns) . " campaigns for account {$accountId}");
        } catch (\Throwable $e) {
            Logging::error("MetaWebhook: Error pausing campaigns for {$accountId}: " . $e->getMessage());
        }
    }

    private function sendAlert(string $message): void
    {
        try {
            $telegramService = app(\App\Service\TelegramService::class);
            $groupId = config('services.telegram.support_group_id');
            if (!empty($groupId)) {
                $telegramService->sendNotification($groupId, $message);
            }
        } catch (\Throwable $e) {
            Logging::error('MetaWebhook: Failed to send alert: ' . $e->getMessage());
        }
    }
}
