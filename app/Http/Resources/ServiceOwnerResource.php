<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceOwnerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $package = $this->package;
        $user = $this->user;
        return [
            'id' => $this->id,
            'package' => [
                'id' => $package->id,
                'name' => $package->name,
                'platform' => $package->platform,
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'budget' => $this->budget,
            'status' => $this->status,
            'config_account' => $this->config_account,
            'description' => (string)$this->description,
        ];
    }
}
