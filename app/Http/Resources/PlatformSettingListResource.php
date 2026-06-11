<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PlatformSetting */
class PlatformSettingListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => (string) ($this->name ?? ''),
            'platform' => (int) $this->platform,
            'config' => (array) $this->config,
            'token_status' => (array) (($this->config ?? [])['token_status'] ?? []),
            'disabled' => (bool) $this->disabled,
        ];
    }
}

