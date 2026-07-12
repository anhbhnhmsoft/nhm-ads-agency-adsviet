<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Logging;
use App\Service\MetaService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MetaWebhookController extends Controller
{
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
    public function handle(Request $request): Response
    {
        // ── Verification handshake (GET) ──
        if ($request->isMethod('GET')) {
            return $this->verify($request);
        }

        // ── Event delivery (POST) ──
        return $this->processEvent($request);
    }

    /**
     * Meta webhook verification
     * GET /webhooks/meta?hub.mode=subscribe&hub.challenge=xxx&hub.verify_token=xxx
     */
    private function verify(Request $request): Response
    {
        $mode = (string) $request->query('hub_mode', '');
        $token = (string) $request->query('hub_verify_token', '');
        $challenge = $request->query('hub_challenge');

        $expectedToken = config('services.meta_webhook.verify_token', env('META_WEBHOOK_VERIFY_TOKEN', 'adviet_meta_webhook_2026'));

        if (
            $mode === 'subscribe'
            && $challenge !== null
            && $challenge !== ''
            && hash_equals((string) $expectedToken, $token)
        ) {
            Logging::web('MetaWebhook: Verification successful');
            return response((string) $challenge, Response::HTTP_OK)
                ->header('Content-Type', 'text/plain; charset=UTF-8');
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
    private function processEvent(Request $request): Response
    {
        $payload = $request->all();
        $object = $payload['object'] ?? null;

        Logging::web('MetaWebhook: Event received', [
            'object' => $object,
            'entry_count' => count($payload['entry'] ?? []),
        ]);

        // Hỗ trợ cả ad_account và campaign events
        if (!in_array($object, ['ad_account', 'campaign'])) {
            return response()->json(['status' => 'ignored', 'object' => $object]);
        }

        $entries = $payload['entry'] ?? [];
        foreach ($entries as $entry) {
            $this->processEntry($entry, $object);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Process a single webhook entry
     */
    private function processEntry(array $entry, string $object): void
    {
        $objectId = $entry['id'] ?? null;
        $changes = $entry['changes'] ?? [];

        if (!$objectId) return;

        foreach ($changes as $change) {
            $field = $change['field'] ?? null;
            $value = $change['value'] ?? null;

            Logging::web('MetaWebhook: Change received', [
                'object' => $object,
                'object_id' => $objectId,
                'field' => $field,
                'value' => is_array($value) ? json_encode($value) : $value,
            ]);

            match (true) {
                // Ad Account events
                $object === 'ad_account' && $field === 'ad_status' =>
                    $this->handleAccountStatusChange($objectId, $value),
                $object === 'ad_account' && $field === 'spend_cap' =>
                    $this->handleSpendCapChange($objectId, $value),
                // Campaign events
                $object === 'campaign' && str_contains((string) $field, 'campaign') =>
                    $this->handleCampaignChange($objectId, $value, $field),
                // Default
                default =>
                    Logging::web("MetaWebhook: Unhandled field: {$field}", ['object_id' => $objectId]),
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
     * Handle campaign status change
     */
    private function handleCampaignChange(string $campaignId, $value, string $field): void
    {
        // Meta gửi value là object chứa campaign_id và status
        $campaignStatus = null;
        if (is_array($value)) {
            $campaignStatus = $value['status'] ?? $value['effective_status'] ?? null;
        }

        if (!$campaignStatus) {
            Logging::web("MetaWebhook: Campaign event no status", ['campaign_id' => $campaignId, 'field' => $field]);
            return;
        }

        Logging::web("MetaWebhook: Campaign status change", [
            'campaign_id' => $campaignId,
            'status' => $campaignStatus,
        ]);

        // Update status trong DB
        try {
            $updated = \App\Models\MetaAdsCampaign::query()
                ->where('campaign_id', $campaignId)
                ->update([
                    'status' => $campaignStatus,
                    'effective_status' => $campaignStatus,
                    'last_synced_at' => now(),
                ]);

            if ($updated > 0) {
                Logging::web("MetaWebhook: Campaign {$campaignId} updated to {$campaignStatus}");
            }
        } catch (\Throwable $e) {
            Logging::error("MetaWebhook: Failed to update campaign {$campaignId}: " . $e->getMessage());
        }

        // Alert nếu campaign bị pause từ Meta
        if (in_array($campaignStatus, ['PAUSED', 'DELETED'])) {
            $this->sendAlert("⚠️ Campaign {$campaignId} changed to {$campaignStatus} từ Meta webhook.");
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
