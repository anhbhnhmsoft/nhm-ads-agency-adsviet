<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BusinessManagerListResource extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function ($item) {
                return [
                    'id' => $item['id'] ?? null,
                    'name' => $item['name'] ?? 'Unknown',
                    'platform' => $item['platform'] ?? null,
                    'owner_name' => $item['owner_name'] ?? 'Unknown',
                    'owner_id' => $item['owner_id'] ?? null,
                    'total_accounts' => $item['total_accounts'] ?? 0,
                    'active_accounts' => $item['active_accounts'] ?? 0,
                    'disabled_accounts' => $item['disabled_accounts'] ?? 0,
                    'total_spend' => $item['total_spend'] ?? '0',
                    'total_balance' => $item['total_balance'] ?? '0',
                    'currency' => $item['currency'] ?? 'USD',
                ];
            }),
        ];
    }
}

