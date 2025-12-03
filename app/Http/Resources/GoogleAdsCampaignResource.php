<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class GoogleAdsCampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $statusCode = strtoupper($this->status ?? $this->effective_status ?? 'UNKNOWN');
        $statusKey = Str::of($statusCode)->lower()->replace('-', '_')->__toString();
        
        // Xác định severity
        $errorStatuses = ['REMOVED', 'SUSPENDED', 'UNKNOWN'];
        $warningStatuses = ['PAUSED'];
        $severity = in_array($statusCode, $errorStatuses, true) ? 'error' : (in_array($statusCode, $warningStatuses, true) ? 'warning' : 'success');
        
        // Lấy label từ i18n
        $label = __('google_ads.campaign_status.' . $statusKey);
        if ($label === 'google_ads.campaign_status.' . $statusKey) {
            $label = __('google_ads.campaign_status.unknown');
        }

        return [
            'id' => $this->id,
            'service_user_id' => $this->service_user_id,
            'google_account_id' => $this->google_account_id,
            'campaign_id' => $this->campaign_id,
            'name' => $this->name,
            'status' => $this->status,
            'effective_status' => $this->effective_status,
            'status_label' => $label,
            'status_severity' => $severity,
            'objective' => $this->objective,
            'daily_budget' => $this->daily_budget,
            'budget_remaining' => $this->budget_remaining,
            'total_spend' => (string) ($this->total_spend ?? '0'),
            'today_spend' => (string) ($this->today_spend ?? '0'),
            'start_time' => $this->start_time?->toIso8601String(),
            'stop_time' => $this->stop_time?->toIso8601String(),
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
        ];
    }
}

