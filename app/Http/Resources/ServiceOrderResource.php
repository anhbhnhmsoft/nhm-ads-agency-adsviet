<?php

namespace App\Http\Resources;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\ServiceUser\ServiceUserStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = ServiceUserStatus::tryFrom((int) $this->status);
        $package = $this->package;

        $user = $this->whenLoaded('user');
        $referral = $user?->referredBy?->referrer;

        return [
            'id' => (string) $this->id,
            'status' => $this->status,
            'status_label' => $status?->name,
            'package' => [
                'id' => $package?->id,
                'name' => $package?->name,
                'platform' => $package?->platform,
                'platform_label' => $package ? PlatformType::tryFrom((int) $package->platform)?->label() : null,
            ],
            'user' => [
                'referrer' => $referral ? [
                    'name' => $referral->name,
                ] : null,
            ],
            'budget' => $this->budget,
            'open_fee' => $package?->open_fee,
            'top_up_fee' => $package?->top_up_fee,
            'config_account' => $this->config_account,
            'description' => $this->description,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}

