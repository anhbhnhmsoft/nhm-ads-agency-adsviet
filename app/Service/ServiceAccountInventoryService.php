<?php

namespace App\Service;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\ServicePackage\ServiceAccountInventoryStatus;
use App\Common\Constants\ServiceUser\ServiceUserStatus;
use App\Core\Logging;
use App\Core\ServiceReturn;
use App\Models\GoogleAccount;
use App\Models\MetaAccount;
use App\Models\ServiceAccountInventory;
use App\Models\ServiceUser;
use App\Repositories\ServiceAccountInventoryRepository;
use App\Repositories\ServicePackageRepository;
use Illuminate\Support\Facades\DB;
use Throwable;

class ServiceAccountInventoryService
{
    public function __construct(
        protected ServiceAccountInventoryRepository $inventoryRepository,
        protected ServicePackageRepository $servicePackageRepository,
    ) {
    }

    public function listForPackage(string $packageId): ServiceReturn
    {
        $items = $this->inventoryRepository->query()
            ->where('service_package_id', $packageId)
            ->orderByRaw("case status when 'available' then 1 when 'reserved' then 2 when 'assigned' then 3 else 4 end")
            ->orderByDesc('created_at')
            ->get();

        return ServiceReturn::success(data: $items);
    }

    public function importForPackage(string $packageId, array $accounts): ServiceReturn
    {
        $package = $this->servicePackageRepository->find($packageId);
        if (!$package) {
            return ServiceReturn::error(message: __('Gói dịch vụ không tồn tại'));
        }

        $created = 0;
        $updated = 0;

        try {
            DB::transaction(function () use ($accounts, $package, &$created, &$updated) {
                foreach ($accounts as $account) {
                    $accountId = trim((string) ($account['account_id'] ?? ''));
                    if ($accountId === '') {
                        continue;
                    }

                    $source = $this->findSourceAccount((int) $package->platform, $accountId);
                    $payload = [
                        'service_package_id' => (string) $package->id,
                        'platform' => (int) $package->platform,
                        'account_id' => $accountId,
                        'account_name' => $account['account_name'] ?? $source?->account_name,
                        'business_manager_id' => $account['business_manager_id'] ?? ($source instanceof MetaAccount ? $source->business_manager_id : null),
                        'customer_manager_id' => $account['customer_manager_id'] ?? ($source instanceof GoogleAccount ? $source->customer_manager_id : null),
                        'source_account_type' => $source ? $source::class : null,
                        'source_account_id' => $source?->id,
                        'metadata' => array_filter([
                            'note' => $account['note'] ?? null,
                            'imported_at' => now()->toDateTimeString(),
                        ]),
                    ];

                    /** @var ServiceAccountInventory|null $inventory */
                    $inventory = $this->inventoryRepository->query()
                        ->where('service_package_id', $package->id)
                        ->where('platform', (int) $package->platform)
                        ->where('account_id', $accountId)
                        ->first();

                    if ($inventory) {
                        $inventory->fill($payload);
                        if ($inventory->status === ServiceAccountInventoryStatus::FAILED->value) {
                            $inventory->status = ServiceAccountInventoryStatus::AVAILABLE->value;
                            $inventory->last_error = null;
                        }
                        $inventory->save();
                        $updated++;
                    } else {
                        $this->inventoryRepository->create(array_merge($payload, [
                            'status' => ServiceAccountInventoryStatus::AVAILABLE->value,
                        ]));
                        $created++;
                    }
                }
            });

            return ServiceReturn::success(data: [
                'created' => $created,
                'updated' => $updated,
            ]);
        } catch (Throwable $e) {
            Logging::error(
                message: 'ServiceAccountInventoryService@importForPackage error: ' . $e->getMessage(),
                exception: $e
            );

            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function delete(string $inventoryId): ServiceReturn
    {
        /** @var ServiceAccountInventory|null $inventory */
        $inventory = $this->inventoryRepository->query()->find($inventoryId);
        if (!$inventory) {
            return ServiceReturn::error(message: __('common_error.not_found'));
        }

        if ($inventory->status === ServiceAccountInventoryStatus::ASSIGNED->value) {
            return ServiceReturn::error(message: __('Không thể xoá tài khoản đã giao cho khách'));
        }

        $inventory->delete();

        return ServiceReturn::success();
    }

    public function assignAvailableAccounts(ServiceUser $serviceUser, int $quantity, array $configAccount = []): ServiceReturn
    {
        $package = $serviceUser->package;
        if (!$package || $quantity <= 0) {
            return ServiceReturn::success(data: []);
        }

        try {
            return DB::transaction(function () use ($serviceUser, $package, $quantity, $configAccount) {
                $inventoryTotal = $this->inventoryRepository->query()
                    ->where('service_package_id', $package->id)
                    ->where('platform', (int) $package->platform)
                    ->count();

                if ($inventoryTotal === 0) {
                    return ServiceReturn::success(data: collect());
                }

                $items = $this->inventoryRepository->availableForPackage(
                    (string) $package->id,
                    (int) $package->platform,
                    $quantity
                );

                if ($items->count() < $quantity) {
                    return ServiceReturn::error(message: __('Kho tài khoản không đủ số lượng để giao tự động'));
                }

                $assigned = collect();
                foreach ($items as $index => $inventory) {
                    $target = $this->resolveLinkTarget($configAccount, $index, (int) $package->platform);
                    $source = $this->findSourceAccount((int) $package->platform, (string) $inventory->account_id);

                    if ($source) {
                        $source->service_user_id = $serviceUser->id;
                        $source->save();
                    }

                    $inventory->update([
                        'status' => ServiceAccountInventoryStatus::ASSIGNED->value,
                        'assigned_user_id' => $serviceUser->user_id,
                        'assigned_service_user_id' => $serviceUser->id,
                        'reserved_until' => null,
                        'link_target_type' => $target['type'],
                        'link_target_value' => $target['value'],
                        'source_account_type' => $source ? $source::class : $inventory->source_account_type,
                        'source_account_id' => $source?->id ?? $inventory->source_account_id,
                        'metadata' => array_merge($inventory->metadata ?? [], [
                            'auto_assigned_at' => now()->toDateTimeString(),
                            'auto_link_status' => $source ? 'attached_existing_account' : 'pending_platform_link',
                            'target' => $target,
                        ]),
                        'last_error' => $source ? null : 'Không tìm thấy account đã sync trong hệ thống; đã giữ metadata để admin/API job xử lý liên kết.',
                    ]);

                    $assigned->push($inventory->fresh());
                }

                $currentConfig = $serviceUser->config_account ?? [];
                if (!is_array($currentConfig)) {
                    $currentConfig = [];
                }

                $currentConfig['auto_fulfillment'] = [
                    'status' => 'assigned',
                    'assigned_at' => now()->toDateTimeString(),
                    'accounts' => $assigned->map(fn (ServiceAccountInventory $item) => [
                        'inventory_id' => (string) $item->id,
                        'account_id' => $item->account_id,
                        'account_name' => $item->account_name,
                        'platform' => $item->platform,
                        'link_target_type' => $item->link_target_type,
                        'link_target_value' => $item->link_target_value,
                    ])->values()->all(),
                ];

                $serviceUser->config_account = $currentConfig;
                $serviceUser->status = ServiceUserStatus::ACTIVE->value;
                $serviceUser->save();

                return ServiceReturn::success(data: $assigned);
            });
        } catch (Throwable $e) {
            Logging::error(
                message: 'ServiceAccountInventoryService@assignAvailableAccounts error: ' . $e->getMessage(),
                exception: $e
            );

            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function releaseForServiceUser(string $serviceUserId): void
    {
        $this->inventoryRepository->releaseForServiceUser($serviceUserId);
    }

    protected function findSourceAccount(int $platform, string $accountId): MetaAccount|GoogleAccount|null
    {
        $normalizedAccountId = preg_replace('/^act_/', '', trim($accountId));

        if ($platform === PlatformType::META->value) {
            return MetaAccount::query()
                ->where(function ($query) use ($accountId, $normalizedAccountId) {
                    $query->where('account_id', $accountId)
                        ->orWhere('account_id', $normalizedAccountId)
                        ->orWhere('account_id', 'act_' . $normalizedAccountId);
                })
                ->first();
        }

        if ($platform === PlatformType::GOOGLE->value) {
            return GoogleAccount::query()
                ->where(function ($query) use ($accountId, $normalizedAccountId) {
                    $query->where('account_id', $accountId)
                        ->orWhere('account_id', $normalizedAccountId);
                })
                ->first();
        }

        return null;
    }

    protected function resolveLinkTarget(array $configAccount, int $index, int $platform): array
    {
        $accountConfig = $configAccount['accounts'][$index] ?? null;
        $email = $accountConfig['meta_email'] ?? $configAccount['meta_email'] ?? null;
        $managerIds = $accountConfig['bm_ids'] ?? null;
        $managerId = is_array($managerIds)
            ? ($managerIds[0] ?? null)
            : ($configAccount['bm_id'] ?? null);

        if ($managerId) {
            return [
                'type' => $platform === PlatformType::GOOGLE->value ? 'mcc' : 'business_manager',
                'value' => (string) $managerId,
            ];
        }

        return [
            'type' => 'email',
            'value' => $email ? (string) $email : null,
        ];
    }
}
