<?php

namespace App\Http\Resources;

use App\Common\Constants\Wallet\WalletTransactionStatus;
use App\Common\Constants\Wallet\WalletTransactionType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
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
            'amount' => (float) $this->amount,
            'type' => $this->type,
            'status' => $this->status,
            'description' => $this->description,
            'network' => $this->network,
            'txHash' => $this->tx_hash,
            'payment_id' => $this->payment_id,
            'withdraw_info' => $this->withdraw_info,
            'createdAt' => optional($this->created_at)->toIso8601String(),
            'user' => ($this->wallet && $this->wallet->user) ? [
                'id' => (string) $this->wallet->user->id,
                'name' => $this->wallet->user->name,
            ] : null,
        ];
    }
}
