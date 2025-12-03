<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class MetaAdsCampaignResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $statusCode = strtoupper($this->effective_status ?? $this->status ?? 'UNKNOWN');
        $statusKey = Str::of($statusCode)->lower()->replace('-', '_')->__toString();
        
        // Xác định severity
        $errorStatuses = ['WITH_ISSUES', 'DISAPPROVED', 'IN_GRACE_PERIOD', 'PENDING_SETTLEMENT', 'PENDING_RISK_REVIEW', 'PENDING_CLOSURE', 'CLOSED', 'ARCHIVED', 'DELETED', 'ERROR'];
        $warningStatuses = ['PAUSED', 'LIMITED', 'IN_PROCESS', 'PENDING_REVIEW'];
        $severity = in_array($statusCode, $errorStatuses, true) ? 'error' : (in_array($statusCode, $warningStatuses, true) ? 'warning' : 'success');
        
        // Lấy label từ i18n
        $label = __('meta.campaign_status.' . $statusKey);
        if ($label === 'meta.campaign_status.' . $statusKey) {
            $label = __('meta.campaign_status.unknown');
        }

        return [
            'id' => $this->id,
            'service_user_id' => $this->service_user_id,
            'meta_account_id' => $this->meta_account_id,
            'campaign_id' => $this->campaign_id,
            'name' => $this->name,
            'status' => $this->status,
            'effective_status' => $this->effective_status,
            'status_label' => $label,
            'status_severity' => $severity,
            'objective' => $this->objective,
            'daily_budget' => $this->daily_budget,
            'budget_remaining' => $this->budget_remaining,
            'total_spend' => (string)$this->total_spend,
            'today_spend' => (string)$this->today_spend,
            'created_time' => $this->created_time,
            'start_time' => $this->start_time,
            'stop_time' => $this->stop_time,
            'last_synced_at' => $this->last_synced_at,
        ];
    }
}
