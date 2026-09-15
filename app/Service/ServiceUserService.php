<?php

namespace App\Service;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\ServicePackage\AccountBillingSource;
use App\Common\Constants\ServicePackage\ServicePackagePaymentType;
use App\Common\Constants\User\UserRole;
use App\Core\Logging;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Core\UserLocale;
use App\Models\ServiceUser;
use App\Common\Constants\ServiceUser\ServiceUserStatus;
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
        protected MetaBusinessService $metaBusinessService,
        protected MetaService $metaService,
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
            $txResult = DB::transaction(function () use ($id, $config) {
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
                $bmIdSubmitted = trim((string) ($config['bm_id'] ?? ''));

                $accountIds = [];
                if (!empty($config['account_ids']) && is_array($config['account_ids'])) {
                    $accountIds = array_values(array_filter(array_map('trim', $config['account_ids'])));
                } elseif (!empty($config['account_id'])) {
                    $accountIds = [trim($config['account_id'])];
                }

                $packagePaymentType = $this->resolvePackagePaymentType($serviceUser->package?->payment_type);
                $paymentType = $this->resolveConfigPaymentType($currentConfig['payment_type'] ?? null, $packagePaymentType);
                $billingSource = $this->resolvePackageBillingSource($serviceUser->package?->billing_source);

                // ── Validate trước khi thay đổi gì ──

                // Validate BM đã có khách KHÁC dùng chưa (chỉ khi có BM ID và assign_mode = bm)
                if ($assignMode === 'bm' && !empty($bmIdSubmitted)) {
                    $existingServiceUser = $this->serviceUserRepository->query()
                        ->where('id', '!=', $serviceUser->id)
                        ->where('user_id', '!=', $serviceUser->user_id)
                        ->where('status', ServiceUserStatus::ACTIVE->value)
                        ->where(function ($q) use ($bmIdSubmitted) {
                            $q->whereRaw("config_account->>'bm_id' = ?", [$bmIdSubmitted])
                              ->orWhereRaw("config_account->>'child_bm_id' = ?", [$bmIdSubmitted]);
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

                // Validate accounts đã có KHÁCH KHÁC dùng chưa
                if ($assignMode === 'account' && !empty($accountIds)) {
                    $activeStatus = ServiceUserStatus::ACTIVE->value;
                    $currentUserId = $serviceUser->user_id;
                    if ($platform === PlatformType::META->value) {
                        foreach ($accountIds as $selectedAccountId) {
                            $normId = preg_replace('/^act_/', '', trim($selectedAccountId));
                            $idsToMatch = array_values(array_unique([$selectedAccountId, $normId, 'act_' . $normId]));
                            $existingOwner = $this->metaAccountRepository->query()
                                ->whereIn('account_id', $idsToMatch)
                                ->where('service_user_id', '!=', $serviceUser->id)
                                ->whereNotNull('service_user_id')
                                ->whereHas('serviceUser', fn ($q) => $q
                                    ->where('status', $activeStatus)
                                    ->where('user_id', '!=', $currentUserId))
                                ->with('serviceUser.user')
                                ->first();
                            if ($existingOwner) {
                                $ownerName = $existingOwner->serviceUser?->user?->name ?? 'khách khác';
                                return ServiceReturn::error(
                                    message: __('services.validation.account_already_used_by_customer', ['name' => $ownerName])
                                );
                            }
                        }
                    } elseif ($platform === PlatformType::GOOGLE->value) {
                        foreach ($accountIds as $selectedAccountId) {
                            $cleanId = preg_replace('/[^0-9]/', '', (string) $selectedAccountId);
                            $idsToMatch = array_values(array_filter(array_unique([$selectedAccountId, $cleanId])));
                            $existingOwner = $this->googleAccountRepository->query()
                                ->whereIn('account_id', $idsToMatch)
                                ->where('service_user_id', '!=', $serviceUser->id)
                                ->whereNotNull('service_user_id')
                                ->whereHas('serviceUser', fn ($q) => $q
                                    ->where('status', $activeStatus)
                                    ->where('user_id', '!=', $currentUserId))
                                ->with('serviceUser.user')
                                ->first();
                            if ($existingOwner) {
                                $ownerName = $existingOwner->serviceUser?->user?->name ?? 'khách khác';
                                return ServiceReturn::error(
                                    message: __('services.validation.account_already_used_by_customer', ['name' => $ownerName])
                                );
                            }
                        }
                    }
                }

                // ── Xây config_account ──
                if (is_array($config['accounts'] ?? null) && !empty($config['accounts'])) {
                    $accounts = $config['accounts'];
                    $newConfig = array_merge($currentConfig, [
                        'accounts' => $accounts,
                        'bm_id' => $bmIdSubmitted ?: ($currentConfig['bm_id'] ?? ''),
                        'account_id' => !empty($accountIds) ? $accountIds[0] : null,
                        'account_ids' => $accountIds,
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
                        'account_id' => !empty($accountIds) ? $accountIds[0] : null,
                        'account_ids' => $accountIds,
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

                // ── Gán tài khoản cho service_user (TRƯỚC khi save status) ──
                if ($assignMode === 'account' && !empty($accountIds)) {
                    if ($platform === PlatformType::META->value) {
                        foreach ($accountIds as $selectedAccountId) {
                            $normId = preg_replace('/^act_/', '', trim($selectedAccountId));
                            $idsToMatch = array_values(array_unique([$selectedAccountId, $normId, 'act_' . $normId]));
                            // Double-check lần cuối trong transaction (lock row)
                            $conflictAccount = $this->metaAccountRepository->query()
                                ->whereIn('account_id', $idsToMatch)
                                ->whereNotNull('service_user_id')
                                ->where('service_user_id', '!=', $serviceUser->id)
                                ->whereHas('serviceUser', fn ($q) => $q
                                    ->where('status', ServiceUserStatus::ACTIVE->value)
                                    ->where('user_id', '!=', $serviceUser->user_id))
                                ->lockForUpdate()
                                ->first();
                            if ($conflictAccount) {
                                $conflictName = $conflictAccount->serviceUser?->user?->name ?? 'khách khác';
                                return ServiceReturn::error(
                                    message: __('services.validation.account_already_used_by_customer', ['name' => $conflictName])
                                );
                            }
                            $this->metaAccountRepository->query()
                                ->whereIn('account_id', $idsToMatch)
                                ->update(['service_user_id' => $serviceUser->id]);
                        }
                    } elseif ($platform === PlatformType::GOOGLE->value) {
                        foreach ($accountIds as $selectedAccountId) {
                            $cleanId = preg_replace('/[^0-9]/', '', (string) $selectedAccountId);
                            $idsToMatch = array_values(array_filter(array_unique([$selectedAccountId, $cleanId])));
                            $conflictAccount = $this->googleAccountRepository->query()
                                ->whereIn('account_id', $idsToMatch)
                                ->whereNotNull('service_user_id')
                                ->where('service_user_id', '!=', $serviceUser->id)
                                ->whereHas('serviceUser', fn ($q) => $q
                                    ->where('status', ServiceUserStatus::ACTIVE->value)
                                    ->where('user_id', '!=', $serviceUser->user_id))
                                ->lockForUpdate()
                                ->first();
                            if ($conflictAccount) {
                                $conflictName = $conflictAccount->serviceUser?->user?->name ?? 'khách khác';
                                return ServiceReturn::error(
                                    message: __('services.validation.account_already_used_by_customer', ['name' => $conflictName])
                                );
                            }
                            $this->googleAccountRepository->query()
                                ->whereIn('account_id', $idsToMatch)
                                ->update(['service_user_id' => $serviceUser->id]);

                            if (empty($newConfig['google_manager_id'])) {
                                $detectedManagerId = $this->googleAccountRepository->query()
                                    ->whereIn('account_id', $idsToMatch)
                                    ->whereNotNull('customer_manager_id')
                                    ->where('customer_manager_id', '!=', '')
                                    ->value('customer_manager_id');
                                if ($detectedManagerId) {
                                    $newConfig['google_manager_id'] = $detectedManagerId;
                                }
                            }
                        }
                    }
                }

                // ── Lưu config và cập nhật status ──
                $serviceUser->config_account = $newConfig;
                $serviceUser->status = ServiceUserStatus::ACTIVE->value;
                $serviceUser->save();

                return ServiceReturn::success(data: [
                    'service_user' => $serviceUser,
                    'platform' => $platform,
                    'assign_mode' => $assignMode,
                    'bm_id_submitted' => $bmIdSubmitted,
                    'account_ids' => $accountIds,
                    'top_up_amount' => (float) ($newConfig['top_up_amount'] ?? $currentConfig['top_up_amount'] ?? 0),
                ]);
            });

            if ($txResult->isError()) {
                return $txResult;
            }

            $txData = $txResult->getData();
            /** @var ServiceUser $serviceUser */
            $serviceUser = $txData['service_user'];
            $platform = $txData['platform'];
            $assignMode = $txData['assign_mode'];
            $bmIdSubmitted = $txData['bm_id_submitted'];
            $accountIds = $txData['account_ids'];
            $topUpAmount = $txData['top_up_amount'];

            // ── CÁC TÁC VỤ HẬU PHÊ DUYỆT (CHẠY NGOÀI TRANSACTION ĐỂ TRÁNH LỖI 25P02) ──

            // 1. Sync accounts từ Meta / Google API
            try {
                if ($platform === PlatformType::META->value) {
                    $this->metaService->syncMetaAccounts($serviceUser);
                } elseif ($platform === PlatformType::GOOGLE->value) {
                    if ($assignMode === 'bm' && !empty($bmIdSubmitted)) {
                        $this->syncGoogleAccountsForBm($serviceUser, $bmIdSubmitted);
                    }
                }
            } catch (\Throwable $syncEx) {
                Logging::error('ServiceUserService@approveServiceUser sync accounts failed: '.$syncEx->getMessage(), exception: $syncEx);
            }

            // 2. Nâng spend_cap Meta
            if ($platform === PlatformType::META->value && $topUpAmount > 0 && !empty($accountIds)) {
                foreach ($accountIds as $accId) {
                    try {
                        $spendCapResult = $this->metaBusinessService->increaseAdAccountSpendCap(
                            $accId,
                            $topUpAmount
                        );
                        if ($spendCapResult->isError()) {
                            Logging::error(
                                message: 'ServiceUserService@approveServiceUser increaseAdAccountSpendCap failed: '.$spendCapResult->getMessage(),
                                context: ['account_id' => $accId, 'top_up_amount' => $topUpAmount]
                            );
                        }
                    } catch (\Throwable $spendCapEx) {
                        Logging::error('ServiceUserService@approveServiceUser increaseAdAccountSpendCap error: '.$spendCapEx->getMessage(), exception: $spendCapEx);
                    }
                }
            }

            // 3. Dispatch sync jobs (backup)
            try {
                if ($platform === PlatformType::META->value) {
                    SyncMetaJob::dispatch($serviceUser);
                } elseif ($platform === PlatformType::GOOGLE->value) {
                    SyncGoogleServiceUserJob::dispatch($serviceUser);
                }
            } catch (\Throwable $dispatchEx) {
                Logging::error('ServiceUserService@approveServiceUser dispatch sync job error: '.$dispatchEx->getMessage(), exception: $dispatchEx);
            }

            // 4. Gửi thông báo kích hoạt đơn cho khách
            try {
                $this->notifyServiceStatus($serviceUser, 'activated');
            } catch (\Throwable $notifEx) {
                Logging::error('ServiceUserService@approveServiceUser notify error: '.$notifEx->getMessage(), exception: $notifEx);
            }

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

    /**
     * Gỡ gán tài khoản khỏi service_user → trả account về kho available
     */
    public function unassignAccount(string $serviceUserId, string $accountId): ServiceReturn
    {
        try {
            return DB::transaction(function () use ($serviceUserId, $accountId) {
                $serviceUser = $this->serviceUserRepository->query()->find($serviceUserId);
                if (!$serviceUser) {
                    return ServiceReturn::error(message: __('common_error.not_found'));
                }

                $platform = $serviceUser->package?->platform ?? null;

                // Tìm và gỡ account — chỉ cần match account_id (vì có thể bị overwrite sai service_user_id)
                $normalizedAccountId = preg_replace('/^act_/', '', trim($accountId));
                $found = false;
                if ((int) $platform === PlatformType::META->value) {
                    $account = $this->metaAccountRepository->query()
                        ->where(function ($q) use ($accountId, $normalizedAccountId) {
                            $q->where('account_id', $accountId)
                              ->orWhere('account_id', $normalizedAccountId);
                        })
                        ->first();
                    if ($account) {
                        $account->update(['service_user_id' => null]);
                        $found = true;
                    }
                } elseif ((int) $platform === PlatformType::GOOGLE->value) {
                    $account = $this->googleAccountRepository->query()
                        ->where('account_id', $accountId)
                        ->first();
                    if ($account) {
                        $account->update(['service_user_id' => null]);
                        $found = true;
                    }
                }

                if (!$found) {
                    return ServiceReturn::error(message: __('services.validation.account_not_linked'));
                }

                // Chuyển đơn về admin (Adviet Agency FB) — giữ ACTIVE
                $adminBmId = '1537217683931546';
                $config = $serviceUser->config_account ?? [];
                if (!is_array($config)) {
                    $config = [];
                }
                unset($config['account_id'], $config['accounts']);
                $config['bm_id'] = $adminBmId;
                $config['assign_mode'] = 'bm';
                $serviceUser->config_account = $config;
                // Giữ nguyên status (không đổi về PENDING)
                $serviceUser->save();

                return ServiceReturn::success();
            });
        } catch (\Throwable $e) {
            Logging::error(
                message: 'ServiceUserService@unassignAccount error: '.$e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    private function syncGoogleAccountsForBm(ServiceUser $serviceUser, string $bmId): void
    {
        try {
            $this->googleAccountRepository->query()
                ->where('customer_manager_id', $bmId)
                ->whereNull('service_user_id')
                ->update(['service_user_id' => $serviceUser->id]);
        } catch (\Throwable $e) {
            Logging::error(
                message: 'ServiceUserService@syncGoogleAccountsForBm error: '.$e->getMessage(),
                exception: $e
            );
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

            UserLocale::run($user, function () use ($user, $serviceUser, $statusKey) {
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
            });
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
                // Lưu account_ids nếu có
                if (!empty($config['account_ids'])) {
                    $updateData['account_ids'] = array_values(array_filter($config['account_ids']));
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

            // Gán tài khoản nếu assign_mode = account (hỗ trợ multi-account qua account_ids)
            $assignMode = $config['assign_mode'] ?? null;
            $platform   = $serviceUser->package?->platform;
            if ($assignMode === 'account') {
                // Ưu tiên account_ids array, fallback về account_id
                $accountIds = !empty($config['account_ids'])
                    ? array_values(array_filter($config['account_ids']))
                    : (!empty($config['account_id']) ? [$config['account_id']] : []);

                // Gỡ gán các tài khoản cũ thuộc về service_user này để tránh rác/trùng lặp khi update danh sách mới
                if ($platform === PlatformType::META->value) {
                    $this->metaAccountRepository->query()
                        ->where('service_user_id', $serviceUser->id)
                        ->update(['service_user_id' => null]);
                } elseif ($platform === PlatformType::GOOGLE->value) {
                    $this->googleAccountRepository->query()
                        ->where('service_user_id', $serviceUser->id)
                        ->update(['service_user_id' => null]);
                }

                foreach ($accountIds as $selectedAccountId) {
                    if (!$selectedAccountId) continue;
                    if ($platform === PlatformType::META->value) {
                        $normId = preg_replace('/^act_/', '', trim($selectedAccountId));
                        $idsToMatch = array_values(array_unique([$selectedAccountId, $normId, 'act_' . $normId]));
                        // Defensive check
                        $conflict = $this->metaAccountRepository->query()
                            ->whereIn('account_id', $idsToMatch)
                            ->whereNotNull('service_user_id')
                            ->where('service_user_id', '!=', $serviceUser->id)
                            ->whereHas('serviceUser', fn($q) => $q
                                ->where('status', ServiceUserStatus::ACTIVE->value)
                                ->where('user_id', '!=', $serviceUser->user_id))
                            ->first();
                        if ($conflict) {
                            $conflictName = $conflict->serviceUser?->user?->name ?? 'khách khác';
                            Logging::error('updateConfigAccount: account conflict', [
                                'account_id' => $selectedAccountId,
                                'conflict_user' => $conflictName,
                            ]);
                            continue; // Bỏ qua account đang bị khách khác dùng
                        }
                        $this->metaAccountRepository->query()
                            ->whereIn('account_id', $idsToMatch)
                            ->update(['service_user_id' => $serviceUser->id]);
                    } elseif ($platform === PlatformType::GOOGLE->value) {
                        $cleanId = preg_replace('/[^0-9]/', '', (string) $selectedAccountId);
                        $idsToMatch = array_values(array_filter(array_unique([$selectedAccountId, $cleanId])));
                        $conflict = $this->googleAccountRepository->query()
                            ->whereIn('account_id', $idsToMatch)
                            ->whereNotNull('service_user_id')
                            ->where('service_user_id', '!=', $serviceUser->id)
                            ->whereHas('serviceUser', fn($q) => $q
                                ->where('status', ServiceUserStatus::ACTIVE->value)
                                ->where('user_id', '!=', $serviceUser->user_id))
                            ->first();
                        if ($conflict) continue;
                        $this->googleAccountRepository->query()
                            ->whereIn('account_id', $idsToMatch)
                            ->update(['service_user_id' => $serviceUser->id]);

                        if (empty($serviceUser->config_account['google_manager_id'])) {
                            $detectedManagerId = $this->googleAccountRepository->query()
                                ->whereIn('account_id', $idsToMatch)
                                ->whereNotNull('customer_manager_id')
                                ->where('customer_manager_id', '!=', '')
                                ->value('customer_manager_id');
                            if ($detectedManagerId) {
                                $updatedConfig = $serviceUser->config_account ?? [];
                                $updatedConfig['google_manager_id'] = $detectedManagerId;
                                $serviceUser->config_account = $updatedConfig;
                                $serviceUser->save();
                            }
                        }
                    }
                }
            }

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

    /**
     * Admin/Manager/Employee đồng bộ chi tiêu mới nhất từ API và thu phí ngay cho đơn hàng trả sau
     */
    public function syncAndBillPostpay(string $id): ServiceReturn
    {
        try {
            $serviceUser = $this->serviceUserRepository->query()
                ->with(['package', 'user.wallet'])
                ->find($id);

            if (!$serviceUser) {
                return ServiceReturn::error(message: __('common_error.not_found'));
            }

            // Bước 1: Đồng bộ dữ liệu tài khoản từ Platform API
            $platform = (int) ($serviceUser->package?->platform ?? 0);
            if ($platform === PlatformType::META->value) {
                $syncResult = $this->metaService->syncMetaAccounts($serviceUser);
                if ($syncResult->isError()) {
                    Logging::error('syncAndBillPostpay: syncMetaAccounts error: ' . $syncResult->getMessage(), [
                        'service_user_id' => $serviceUser->id,
                    ]);
                }
            } elseif ($platform === PlatformType::GOOGLE->value) {
                $googleSyncJob = new \App\Jobs\GoogleAds\SyncGoogleServiceUserJob($serviceUser);
                $googleSyncJob->handle(app(\App\Service\GoogleAdsService::class));
            }

            // Bước 2: Tính toán chi tiêu thực tế và phí chưa thu
            $config = $serviceUser->config_account ?? [];
            if (!is_array($config)) {
                $config = [];
            }

            $spendingFeePercent = (float) ($serviceUser->package?->spending_fee ?? 0);
            if ($spendingFeePercent <= 0 && ($serviceUser->package?->billing_source === AccountBillingSource::CUSTOMER_CARD->value)) {
                $spendingFeePercent = (float) ($serviceUser->package?->top_up_fee ?? 0);
            }

            $currencyService = app(\App\Service\CurrencyExchangeService::class);
            $zeroDecimal = ['BIF','CLP','DJF','GNF','ISK','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'];

            $totalSpend = 0.0;
            if ($platform === PlatformType::META->value) {
                $metaAccounts = DB::table('meta_accounts')
                    ->where('service_user_id', $serviceUser->id)
                    ->whereNull('deleted_at')
                    ->select('amount_spent', 'currency')
                    ->get();
                foreach ($metaAccounts as $a) {
                    $raw = (float) ($a->amount_spent ?? 0);
                    $curr = strtoupper($a->currency ?? 'USD');
                    $amt = in_array($curr, $zeroDecimal) ? $raw : $raw / 100;
                    $totalSpend += $currencyService->convert($amt, $curr, 'USD');
                }
            } elseif ($platform === PlatformType::GOOGLE->value) {
                $googleAccounts = DB::table('google_accounts')
                    ->where('service_user_id', $serviceUser->id)
                    ->whereNull('deleted_at')
                    ->select('amount_spent', 'currency')
                    ->get();
                foreach ($googleAccounts as $a) {
                    $raw = (float) ($a->amount_spent ?? 0);
                    $curr = strtoupper($a->currency ?? 'USD');
                    $amt = in_array($curr, $zeroDecimal) ? $raw : $raw / 100;
                    $totalSpend += $currencyService->convert($amt, $curr, 'USD');
                }
            }

            $billedSpend = 0.0;
            if (isset($config['spending_fee_billed_spend']) && is_numeric($config['spending_fee_billed_spend'])) {
                $billedSpend = max(0.0, (float) $config['spending_fee_billed_spend']);
            }

            $unbilledSpend = max(0.0, $totalSpend - $billedSpend);

            if ($unbilledSpend <= 0) {
                return ServiceReturn::success(
                    data: [
                        'total_spend' => $totalSpend,
                        'billed_spend' => $billedSpend,
                        'unbilled_spend' => 0.0,
                        'charge_amount' => 0.0,
                    ],
                    message: __('services.flash.sync_and_bill_no_unbilled', [
                        'total_spend' => number_format($totalSpend, 2),
                    ])
                );
            }

            $chargeAmount = round($unbilledSpend * ($spendingFeePercent / 100), 2);
            if ($chargeAmount <= 0) {
                return ServiceReturn::success(
                    data: [
                        'total_spend' => $totalSpend,
                        'billed_spend' => $billedSpend,
                        'unbilled_spend' => $unbilledSpend,
                        'charge_amount' => 0.0,
                    ],
                    message: __('services.flash.sync_and_bill_fee_zero')
                );
            }

            $wallet = $this->walletRepository->findByUserId((string) $serviceUser->user_id);
            if (!$wallet) {
                return ServiceReturn::error(message: __('wallet.error.wallet_not_found'));
            }

            if ((float) $wallet->balance < $chargeAmount) {
                return ServiceReturn::error(
                    message: __('services.flash.sync_and_bill_insufficient_balance', [
                        'unbilled_spend' => number_format($unbilledSpend, 2),
                        'charge_amount' => number_format($chargeAmount, 2),
                        'balance' => number_format((float) $wallet->balance, 2),
                    ])
                );
            }

            // Thực hiện trừ tiền ví và ghi log với lock row chống duplicate/spam charge
            return DB::transaction(function () use ($serviceUser, $spendingFeePercent, $totalSpend) {
                $lockedServiceUser = $this->serviceUserRepository->query()
                    ->with(['package'])
                    ->where('id', $serviceUser->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedServiceUser) {
                    return ServiceReturn::error(message: __('common_error.not_found'));
                }

                $currentConfig = $lockedServiceUser->config_account ?? [];
                if (!is_array($currentConfig)) {
                    $currentConfig = [];
                }

                $currentBilledSpend = max(0.0, (float) ($currentConfig['spending_fee_billed_spend'] ?? 0.0));
                $recalculatedUnbilledSpend = max(0.0, $totalSpend - $currentBilledSpend);

                if ($recalculatedUnbilledSpend <= 0) {
                    return ServiceReturn::success(
                        data: [
                            'total_spend' => $totalSpend,
                            'billed_spend' => $currentBilledSpend,
                            'unbilled_spend' => 0.0,
                            'charge_amount' => 0.0,
                        ],
                        message: __('services.flash.sync_and_bill_no_unbilled', [
                            'total_spend' => number_format($totalSpend, 2),
                        ])
                    );
                }

                $recalculatedChargeAmount = round($recalculatedUnbilledSpend * ($spendingFeePercent / 100), 2);
                if ($recalculatedChargeAmount <= 0) {
                    return ServiceReturn::success(
                        data: [
                            'total_spend' => $totalSpend,
                            'billed_spend' => $currentBilledSpend,
                            'unbilled_spend' => $recalculatedUnbilledSpend,
                            'charge_amount' => 0.0,
                        ],
                        message: __('services.flash.sync_and_bill_fee_zero')
                    );
                }

                $wallet = \App\Models\UserWallet::where('user_id', $lockedServiceUser->user_id)
                    ->lockForUpdate()
                    ->first();

                if (!$wallet) {
                    return ServiceReturn::error(message: __('wallet.error.wallet_not_found'));
                }

                if ((float) $wallet->balance < $recalculatedChargeAmount) {
                    return ServiceReturn::error(
                        message: __('services.flash.sync_and_bill_insufficient_balance', [
                            'unbilled_spend' => number_format($recalculatedUnbilledSpend, 2),
                            'charge_amount' => number_format($recalculatedChargeAmount, 2),
                            'balance' => number_format((float) $wallet->balance, 2),
                        ])
                    );
                }

                $wallet->update(['balance' => (float) $wallet->balance - $recalculatedChargeAmount]);

                $packageName = $lockedServiceUser->package?->name ?? 'Dịch vụ';
                $walletTransaction = $this->walletTransactionRepository->create([
                    'wallet_id' => $wallet->id,
                    'amount' => -$recalculatedChargeAmount,
                    'type' => WalletTransactionType::SPENDING_FEE->value,
                    'status' => WalletTransactionStatus::COMPLETED->value,
                    'description' => "Manual sync & bill postpay spending fee ({$spendingFeePercent}% on {$recalculatedUnbilledSpend} USD spend from {$currentBilledSpend} to {$totalSpend}): {$packageName}",
                    'reference_id' => (string) $lockedServiceUser->id,
                    'withdraw_info' => [
                        'purpose' => 'spending_fee',
                        'manual_sync' => true,
                        'spend_amount' => $recalculatedUnbilledSpend,
                        'spending_fee_percent' => $spendingFeePercent,
                        'spending_fee_amount' => $recalculatedChargeAmount,
                        'billed_spend_before' => $currentBilledSpend,
                        'billed_spend_after' => $totalSpend,
                        'charged_at' => now()->toDateTimeString(),
                    ],
                ]);

                ServiceUserTransactionLog::create([
                    'service_user_id' => $lockedServiceUser->id,
                    'amount' => $recalculatedChargeAmount,
                    'type' => ServiceUserTransactionType::FEE->value,
                    'status' => ServiceUserTransactionStatus::COMPLETED->value,
                    'reference_id' => (string) $walletTransaction->id,
                    'description' => "Manual sync & bill postpay spending fee ({$spendingFeePercent}% on {$recalculatedUnbilledSpend} USD spend from {$currentBilledSpend} to {$totalSpend}): {$packageName}",
                ]);

                $currentConfig['spending_fee_billed_spend'] = $totalSpend;
                $currentConfig['spending_fee_last_charged_at'] = now()->toDateTimeString();
                $lockedServiceUser->config_account = $currentConfig;
                $lockedServiceUser->last_postpay_billed_at = now();
                $lockedServiceUser->save();

                app(WalletTransactionService::class)->notifySupportGroupSpendingFee(
                    $walletTransaction,
                    $packageName,
                    $recalculatedUnbilledSpend,
                    $recalculatedChargeAmount,
                );

                return ServiceReturn::success(
                    data: [
                        'total_spend' => $totalSpend,
                        'billed_spend' => $totalSpend,
                        'unbilled_spend' => 0.0,
                        'charge_amount' => $recalculatedChargeAmount,
                        'new_balance' => (float) $wallet->balance,
                    ],
                    message: __('services.flash.sync_and_bill_success', [
                        'charge_amount' => number_format($recalculatedChargeAmount, 2),
                        'unbilled_spend' => number_format($recalculatedUnbilledSpend, 2),
                        'balance' => number_format((float) $wallet->balance, 2),
                    ])
                );
            });
        } catch (\Throwable $e) {
            Logging::error('ServiceUserService@syncAndBillPostpay error: ' . $e->getMessage(), [
                'service_user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ServiceReturn::error(message: __('common_error.server_error') . ': ' . $e->getMessage());
        }
    }

}
