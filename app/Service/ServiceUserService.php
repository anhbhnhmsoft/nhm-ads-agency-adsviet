<?php

namespace App\Service;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\ServicePackage\AccountBillingSource;
use App\Common\Constants\ServicePackage\ServicePackagePaymentType;
use App\Common\Constants\User\UserRole;
use App\Core\Logging;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Models\ServiceUser;
use App\Common\Constants\ServiceUser\ServiceUserTransactionStatus;
use App\Common\Constants\ServiceUser\ServiceUserTransactionType;
use App\Common\Constants\Wallet\WalletTransactionStatus;
use App\Common\Constants\Wallet\WalletTransactionType;
use App\Models\ServiceUserTransactionLog;
use App\Repositories\ServicePackageRepository;
use App\Repositories\ServiceUserRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserWalletTransactionRepository;
use App\Repositories\WalletRepository;
use App\Service\UserAlertService;
use App\Service\MailService;
use App\Jobs\MetaApi\SyncMetaJob;
use App\Jobs\MetaApi\SyncMetaPlatformJob;
use App\Jobs\GoogleAds\SyncGoogleServiceUserJob;
use App\Repositories\MetaAccountRepository;
use App\Repositories\GoogleAccountRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceUserService
{

    public function __construct(
        protected ServiceUserRepository    $serviceUserRepository,
        protected ServicePackageRepository $servicePackageRepository,
        protected UserRepository           $userRepository,
        protected WalletRepository         $walletRepository,
        protected UserWalletTransactionRepository $walletTransactionRepository,
        protected UserAlertService         $userAlertService,
        protected MailService              $mailService,
        protected MetaAccountRepository $metaAccountRepository,
        protected GoogleAccountRepository $googleAccountRepository,
        protected ServiceAccountInventoryService $serviceAccountInventoryService,
    )
    {
    }

    public function getListServiceUserPagination(QueryListDTO $queryListDTO): ServiceReturn
    {
        try {
            $user = Auth::user();
            $filter = $queryListDTO->filter ?? [];
            if ($user->role === UserRole::CUSTOMER->value) {
                $filter['user_id'] = $user->id;
            }
            // Tạo query với bộ lọc
            $query = $this->serviceUserRepository->filterQuery($filter);
            $query = $this->serviceUserRepository->withListRelations($query);
            // Sắp xếp
            $query = $this->serviceUserRepository->sortQuery($query, $queryListDTO->sortBy, $queryListDTO->sortDirection);

            $paginator = $query->paginate($queryListDTO->perPage, ['*'], 'page', $queryListDTO->page);
            return ServiceReturn::success(data: $paginator);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi lấy danh sách gói dịch vụ ServiceUserService@getListPagination: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::success(
                data: new LengthAwarePaginator([], 0, $queryListDTO->perPage, $queryListDTO->page)
            );
        }
    }

    /**
     * Admin/Manager/Employee xác nhận đơn dịch vụ
     */
    public function approveServiceUser(string $id, array $config): ServiceReturn
    {
        try {
            /** @var ServiceUser|null $serviceUser */
            $serviceUser = $this->serviceUserRepository->query()
                ->with('package')
                ->find($id);
            if (!$serviceUser) {
                return ServiceReturn::error(message: __('common_error.not_found'));
            }

            $currentConfig = $serviceUser->config_account ?? [];
            if (!is_array($currentConfig)) {
                $currentConfig = [];
            }

            $platform = $serviceUser->package->platform ?? null;
            $assignMode = $config['assign_mode'] ?? 'bm';
            $selectedAccountId = $config['account_id'] ?? null;
            $bmIdSubmitted = trim((string) ($config['bm_id'] ?? ''));

            $packagePaymentType = $this->resolvePackagePaymentType($serviceUser->package?->payment_type);
            $paymentType = $this->resolveConfigPaymentType($currentConfig['payment_type'] ?? null, $packagePaymentType);
            $billingSource = $this->resolvePackageBillingSource($serviceUser->package?->billing_source);

            // ── Validate trước khi thay đổi gì ──

            // Validate BM đã có khách khác dùng chưa (chỉ khi có BM ID)
            if (!empty($bmIdSubmitted)) {
                $existingServiceUser = $this->serviceUserRepository->query()
                    ->where('id', '!=', $serviceUser->id)
                    ->where('status', \App\Common\Constants\ServiceUser\ServiceUserStatus::ACTIVE->value)
                    ->where(function ($q) use ($bmIdSubmitted) {
                        $q->whereJsonContains('config_account->bm_id', $bmIdSubmitted)
                          ->orWhereJsonContains('config_account->child_bm_id', $bmIdSubmitted);
                    })
                    ->with('user:id,name,username')
                    ->first();

                if ($existingServiceUser && $existingServiceUser->user) {
                    $customerName = $existingServiceUser->user->name ?? $existingServiceUser->user->username;
                    return ServiceReturn::error(
                        message: __('services.validation.bm_already_used_by_customer', ['name' => $customerName])
                    );
                }
            }

            // Validate account đã có ai dùng chưa (khi chọn tab Gán tài khoản)
            if ($assignMode === 'account' && $selectedAccountId) {
                if ($platform === PlatformType::META->value) {
                    $existingOwner = $this->metaAccountRepository->query()
                        ->where('account_id', $selectedAccountId)
                        ->where('service_user_id', '!=', $serviceUser->id)
                        ->whereNotNull('service_user_id')
                        ->first();
                    if ($existingOwner) {
                        $ownerName = $existingOwner->serviceUser?->user?->name ?? 'khác';
                        return ServiceReturn::error(
                            message: __('services.validation.account_already_used_by_customer', ['name' => $ownerName])
                        );
                    }
                } elseif ($platform === PlatformType::GOOGLE->value) {
                    $existingOwner = $this->googleAccountRepository->query()
                        ->where('account_id', $selectedAccountId)
                        ->where('service_user_id', '!=', $serviceUser->id)
                        ->whereNotNull('service_user_id')
                        ->first();
                    if ($existingOwner) {
                        $ownerName = $existingOwner->serviceUser?->user?->name ?? 'khác';
                        return ServiceReturn::error(
                            message: __('services.validation.account_already_used_by_customer', ['name' => $ownerName])
                        );
                    }
                }
            }

            // ── Xây config_account ──
            if (is_array($config['accounts']) && !empty($config['accounts'])) {
                $accounts = $config['accounts'];
                $newConfig = array_merge($currentConfig, [
                    'accounts' => $accounts,
                    'bm_id' => $bmIdSubmitted ?: ($currentConfig['bm_id'] ?? ''),
                    'account_id' => $selectedAccountId,
                    'assign_mode' => $assignMode,
                    'payment_type' => $paymentType,
                    'billing_source' => $billingSource,
                ]);

                if ($platform === PlatformType::GOOGLE->value) {
                    $newConfig['google_manager_id'] = $bmIdSubmitted ?: ($currentConfig['google_manager_id'] ?? null);
                }
            } else {
                $childBmId = $config['child_bm_id'] ?? null;
                $newConfig = array_merge($currentConfig, [
                    'meta_email' => $config['meta_email'] ?? ($currentConfig['meta_email'] ?? ''),
                    'display_name' => $config['display_name'] ?? ($currentConfig['display_name'] ?? ''),
                    'bm_id' => $bmIdSubmitted ?: ($currentConfig['bm_id'] ?? ''),
                    'child_bm_id' => $childBmId,
                    'account_id' => $selectedAccountId,
                    'assign_mode' => $assignMode,
                    'timezone_bm' => $config['timezone_bm'] ?? ($currentConfig['timezone_bm'] ?? null),
                    'payment_type' => $paymentType,
                    'billing_source' => $billingSource,
                ]);

                if ($platform === PlatformType::GOOGLE->value) {
                    $newConfig['google_manager_id'] = $bmIdSubmitted ?: ($currentConfig['google_manager_id'] ?? null);
                }

                if ($platform === PlatformType::META->value) {
                    $newConfig['info_fanpage'] = $config['info_fanpage'] ?? ($currentConfig['info_fanpage'] ?? '');
                    $newConfig['info_website'] = $config['info_website'] ?? ($currentConfig['info_website'] ?? '');
                }
            }

            // ── Lưu config và cập nhật status ──
            $serviceUser->config_account = $newConfig;
            $serviceUser->status = \App\Common\Constants\ServiceUser\ServiceUserStatus::ACTIVE->value;
            $serviceUser->save();

            // ── Gán tài khoản cho service_user ──
            if ($assignMode === 'account' && $selectedAccountId) {
                // Gán đúng 1 tài khoản được chọn
                if ($platform === PlatformType::META->value) {
                    $this->metaAccountRepository->query()
                        ->where('account_id', $selectedAccountId)
                        ->update(['service_user_id' => $serviceUser->id]);
                } elseif ($platform === PlatformType::GOOGLE->value) {
                    $this->googleAccountRepository->query()
                        ->where('account_id', $selectedAccountId)
                        ->update(['service_user_id' => $serviceUser->id]);
                }
            } elseif (!empty($bmIdSubmitted)) {
                // Tab "Gán BM": gán tất cả TK chưa có ai dùng trong BM/MCC
                if ($platform === PlatformType::META->value) {
                    // Gán TK theo BM + BM share access
                    $accessibleAccountIds = DB::table('meta_account_business_manager_accesses')
                        ->where('source_bm_id', $bmIdSubmitted)
                        ->pluck('account_id')
                        ->toArray();
                    $this->metaAccountRepository->query()
                        ->where(function ($q) use ($bmIdSubmitted, $accessibleAccountIds) {
                            $q->where('business_manager_id', $bmIdSubmitted);
                            if (!empty($accessibleAccountIds)) {
                                $q->orWhereIn('account_id', $accessibleAccountIds);
                            }
                        })
                        ->whereNull('service_user_id')
                        ->update(['service_user_id' => $serviceUser->id]);
                } elseif ($platform === PlatformType::GOOGLE->value) {
                    $this->googleAccountRepository->query()
                        ->where('customer_manager_id', $bmIdSubmitted)
                        ->whereNull('service_user_id')
                        ->update(['service_user_id' => $serviceUser->id]);
                }
            }

            // ── Dispatch sync jobs ──
            if ($platform === PlatformType::META->value) {
                SyncMetaJob::dispatch($serviceUser);
            } elseif ($platform === PlatformType::GOOGLE->value) {
                SyncGoogleServiceUserJob::dispatch($serviceUser);
            }

            $this->notifyServiceStatus($serviceUser, 'activated');

            return ServiceReturn::success(data: $serviceUser);
        } catch (\Throwable $e) {
            Logging::error(
                message: 'ServiceUserService@approveServiceUser error: '.$e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Admin/Manager/Employee hủy đơn dịch vụ và hoàn tiền
     */
    public function cancelServiceUser(string $id): ServiceReturn
    {
        try {
            return DB::transaction(function () use ($id) {
                $serviceUser = $this->serviceUserRepository->query()->with('package')->find($id);
                if (!$serviceUser) {
                    return ServiceReturn::error(message: __('common_error.not_found'));
                }

                // Chỉ hoàn tiền nếu đơn đang ở trạng thái PENDING (chưa được approve)
                $isPending = $serviceUser->status === \App\Common\Constants\ServiceUser\ServiceUserStatus::PENDING->value;

                if ($isPending) {
                    // Tìm transaction gốc (SERVICE_PURCHASE với reference_id = service_user_id)
                    $originalTransaction = $this->walletTransactionRepository->findByReferenceId(
                        (string) $serviceUser->id,
                        WalletTransactionType::SERVICE_PURCHASE->value
                    );

                    if ($originalTransaction) {
                        // Lấy số tiền đã trừ từ transaction gốc (amount là số âm, nên dùng abs để lấy giá trị dương)
                        $refundAmount = abs((float) $originalTransaction->amount);

                        // Lấy ví của user
                        $wallet = $this->walletRepository->findByUserId($serviceUser->user_id);
                        if ($wallet) {
                            // Lấy tên package để hiển thị trong description (nếu có)
                            $package = $serviceUser->package;
                            $packageName = $package ? $package->name : 'Dịch vụ';

                            // Cộng lại tiền vào ví
                            $wallet->update(['balance' => (float) $wallet->balance + $refundAmount]);

                            $refundTransaction = $this->walletTransactionRepository->create([
                                'wallet_id' => $wallet->id,
                                'amount' => $refundAmount,
                                'type' => WalletTransactionType::REFUND->value,
                                'status' => WalletTransactionStatus::COMPLETED->value,
                                'description' => "Hoàn tiền hủy dịch vụ: {$packageName}",
                                'reference_id' => (string) $serviceUser->id,
                            ]);

                            // Tạo ServiceUserTransactionLog type REFUND
                            ServiceUserTransactionLog::create([
                                'service_user_id' => $serviceUser->id,
                                'amount' => $refundAmount,
                                'type' => ServiceUserTransactionType::REFUND->value,
                                'status' => ServiceUserTransactionStatus::COMPLETED->value,
                                'reference_id' => (string) $refundTransaction->id,
                                'description' => "Hoàn tiền hủy đơn dịch vụ: {$packageName}",
                            ]);
                        }
                    }
                }

                // TẠM ẨN: Trả lại tài khoản về kho (không dùng kho tự động)
                // $this->serviceAccountInventoryService->releaseForServiceUser((string) $serviceUser->id);
                $serviceUser->status = \App\Common\Constants\ServiceUser\ServiceUserStatus::FAILED->value;
                $serviceUser->save();

                $this->notifyServiceStatus($serviceUser, $isPending ? 'cancelled' : 'failed');

                return ServiceReturn::success(data: $serviceUser);
            });
        } catch (\Throwable $e) {
            Logging::error(
                message: 'ServiceUserService@cancelServiceUser error: '.$e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    private function notifyServiceStatus(ServiceUser $serviceUser, string $statusKey): void
    {
        try {
            $serviceUser->loadMissing(['user', 'package']);
            $user = $serviceUser->user;
            if (!$user) {
                return;
            }

            $packageName = $serviceUser->package?->name ?? __('service_user.notifications.unknown_package');
            $message = __('service_user.notifications.' . $statusKey, [
                'package' => $packageName,
            ]);

            $this->userAlertService->sendPlainText(
                $user,
                $message,
                function (MailService $mailService, \App\Models\User $u) use ($packageName, $statusKey) {
                    return $mailService->sendServiceUserStatusAlert(
                        email: $u->email,
                        username: $u->name ?? $u->username,
                        packageName: $packageName,
                        statusKey: $statusKey,
                    );
                }
            );
        } catch (\Throwable $e) {
            Logging::error(
                message: 'ServiceUserService@notifyServiceStatus error: '.$e->getMessage(),
                exception: $e
            );
        }
    }

    // Cập nhật config_account của đơn dịch vụ (để bổ sung BM ID sau)
    public function updateConfigAccount(string $id, array $config): ServiceReturn
    {
        try {
            $serviceUser = $this->serviceUserRepository->query()->with('package')->find($id);
            if (!$serviceUser) {
                return ServiceReturn::error(message: __('common_error.not_found'));
            }

            $currentConfig = $serviceUser->config_account ?? [];
            if (!is_array($currentConfig)) {
                $currentConfig = [];
            }

            $packagePaymentType = $this->resolvePackagePaymentType($serviceUser->package?->payment_type);
            $paymentType = $this->resolveConfigPaymentType($currentConfig['payment_type'] ?? null, $packagePaymentType);
            $billingSource = $this->resolvePackageBillingSource($serviceUser->package?->billing_source);

            if (isset($config['accounts']) && is_array($config['accounts']) && !empty($config['accounts'])) {
                // Clear single-account fields to avoid hybrid configuration
                $singleAccountFields = [
                    'meta_email', 'display_name', 'bm_id', 'assign_mode', 'child_bm_id',
                    'account_id', 'uid', 'account_name', 'timezone_bm', 'info_fanpage', 'info_website'
                ];
                foreach ($singleAccountFields as $field) {
                    if (isset($currentConfig[$field])) {
                        unset($currentConfig[$field]);
                    }
                }

                $serviceUser->config_account = array_merge($currentConfig, array_filter([
                    'accounts' => $config['accounts'],
                    'payment_type' => $paymentType,
                    'billing_source' => $billingSource,
                ], fn($value) => $value !== null));
            } else {
                $updateData = [];
                $fields = [
                    'meta_email', 'display_name', 'bm_id', 'assign_mode', 'child_bm_id',
                    'account_id', 'uid', 'account_name', 'timezone_bm', 'info_fanpage', 'info_website'
                ];
                foreach ($fields as $field) {
                    if (array_key_exists($field, $config)) {
                        $updateData[$field] = $config[$field];
                    }
                }
                $updateData['payment_type'] = $paymentType;
                $updateData['billing_source'] = $billingSource;

                // Clear accounts array to avoid hybrid configuration
                if (isset($currentConfig['accounts'])) {
                    unset($currentConfig['accounts']);
                }

                $serviceUser->config_account = array_merge($currentConfig, $updateData);
            }
            $serviceUser->save();

            // Nếu là Meta, trigger sync để cập nhật business_manager_id trong meta_accounts
            // Sync cả khi có bm_id trong config hoặc có accounts với bm_ids
            if ($serviceUser->package && $serviceUser->package->platform === PlatformType::META->value) {
                $bmId = $serviceUser->config_account['bm_id'] ?? null;
                $hasAccountsWithBmIds = false;
                
                // Kiểm tra xem có accounts với bm_ids không
                if (isset($serviceUser->config_account['accounts']) && is_array($serviceUser->config_account['accounts'])) {
                    foreach ($serviceUser->config_account['accounts'] as $account) {
                        if (isset($account['bm_ids']) && !empty($account['bm_ids'])) {
                            $hasAccountsWithBmIds = true;
                            break;
                        }
                    }
                }
                
                // Dispatch sync nếu có bm_id hoặc có accounts với bm_ids
                if ($bmId || $hasAccountsWithBmIds) {
                    $serviceUser->refresh();
                    
                    try {
                        if ($bmId) {
                            SyncMetaPlatformJob::dispatch($bmId);
                        } else {
                            // Nếu chỉ có accounts với bm_ids, dispatch SyncMetaJob
                            SyncMetaJob::dispatch($serviceUser);
                        }
                    } catch (\Throwable $dispatchError) {
                        Logging::error('ServiceUserService@updateConfigAccount: Failed to dispatch Meta sync job', [
                            'service_user_id' => $id,
                            'error' => $dispatchError->getMessage(),
                            'trace' => $dispatchError->getTraceAsString(),
                        ]);
                    }
                } else {
                    Logging::error('ServiceUserService@updateConfigAccount: NOT triggering Meta sync', [
                        'service_user_id' => $id,
                        'config_account' => $serviceUser->config_account,
                    ]);
                }
            } else {
                Logging::web(
                    'ServiceUserService@updateConfigAccount: NOT Meta platform, skipping sync',
                    [
                        'service_user_id' => $id,
                        'platform' => $serviceUser->package->platform ?? null,
                    ]
                );
            }

            return ServiceReturn::success(data: $serviceUser);
        } catch (\Throwable $e) {
            Logging::error(
                message: 'ServiceUserService@updateConfigAccount error: '.$e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function deleteServiceUser(string $id): ServiceReturn
    {
        try {
            $serviceUser = $this->serviceUserRepository->find($id);
            if (!$serviceUser) {
                return ServiceReturn::error(message: __('common_error.not_found'));
            }

            $serviceUser->delete();

            return ServiceReturn::success(data: $serviceUser);
        } catch (\Throwable $e) {
            Logging::error(
                message: 'ServiceUserService@deleteServiceUser error: '.$e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    private function resolvePackagePaymentType(?string $paymentType): string
    {
        if (in_array($paymentType, ServicePackagePaymentType::getValues(), true)) {
            return $paymentType;
        }

        return ServicePackagePaymentType::PREPAY->value;
    }

    private function resolveConfigPaymentType(?string $paymentType, string $fallback): string
    {
        if (in_array($paymentType, ServicePackagePaymentType::getValues(), true)) {
            return $paymentType;
        }

        return $fallback;
    }

    private function resolvePackageBillingSource(?string $billingSource): string
    {
        if (in_array($billingSource, AccountBillingSource::getValues(), true)) {
            return $billingSource;
        }

        return AccountBillingSource::ADVIET_CARD->value;
    }

}
