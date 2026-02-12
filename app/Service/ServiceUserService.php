<?php

namespace App\Service;

use App\Common\Constants\Platform\PlatformType;
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

            // Merge config_account hiện tại với config mới
            $currentConfig = $serviceUser->config_account ?? [];
            if (!is_array($currentConfig)) {
                $currentConfig = [];
            }

            $platform = $serviceUser->package->platform ?? null;

            if (is_array($config['accounts']) && !empty($config['accounts'])) {
                $accounts = $config['accounts'];
                
                $newConfig = array_merge($currentConfig, [
                    'accounts' => $accounts,
                    'payment_type' => $config['payment_type'] ?? ($currentConfig['payment_type'] ?? 'prepay'),
                ]);
                
                // Lưu bm_id vào google_manager_id nếu là Google Ads
                if ($platform === PlatformType::GOOGLE->value) {
                    $bmId = $config['bm_id'] ?? null;
                    
                    if (empty($bmId) && !empty($accounts[0]['bm_ids']) && is_array($accounts[0]['bm_ids']) && !empty($accounts[0]['bm_ids'][0])) {
                        $bmId = trim((string) $accounts[0]['bm_ids'][0]);
                    }
                    
                    if (!empty($bmId)) {
                        $newConfig['google_manager_id'] = $bmId;
                    } elseif (isset($currentConfig['google_manager_id'])) {
                        $newConfig['google_manager_id'] = $currentConfig['google_manager_id'];
                    }
                }
                
                // Lưu bm_id vào config nếu có 
                $bmIdForConfig = $config['bm_id'] ?? null;
                if (empty($bmIdForConfig) && !empty($accounts[0]['bm_ids']) && is_array($accounts[0]['bm_ids']) && !empty($accounts[0]['bm_ids'][0])) {
                    $bmIdForConfig = trim((string) $accounts[0]['bm_ids'][0]);
                }
                if (!empty($bmIdForConfig)) {
                    $newConfig['bm_id'] = $bmIdForConfig;
                } elseif (isset($currentConfig['bm_id'])) {
                    $newConfig['bm_id'] = $currentConfig['bm_id'];
                }
            } else {
                $bmIdValue = $config['bm_id'] ?? ($currentConfig['bm_id'] ?? '');
                $childBmId = $config['child_bm_id'] ?? null;
                
                $newConfig = array_merge($currentConfig, [
                    'meta_email' => $config['meta_email'] ?? ($currentConfig['meta_email'] ?? ''),
                    'display_name' => $config['display_name'] ?? ($currentConfig['display_name'] ?? ''),
                    'bm_id' => $bmIdValue,
                    'child_bm_id' => $childBmId,
                    'uid' => $config['uid'] ?? ($currentConfig['uid'] ?? null),
                    'account_name' => $config['account_name'] ?? ($currentConfig['account_name'] ?? null),
                    'timezone_bm' => $config['timezone_bm'] ?? ($currentConfig['timezone_bm'] ?? null),
                ]);

                if ($platform === PlatformType::GOOGLE->value) {
                    $newConfig['google_manager_id'] = $config['bm_id'] ?? ($currentConfig['google_manager_id'] ?? null);
                }

                if ($platform === PlatformType::META->value) {
                    $newConfig['info_fanpage'] = $config['info_fanpage'] ?? ($currentConfig['info_fanpage'] ?? '');
                    $newConfig['info_website'] = $config['info_website'] ?? ($currentConfig['info_website'] ?? '');
                }
            }

            $serviceUser->config_account = $newConfig;
            $serviceUser->status = \App\Common\Constants\ServiceUser\ServiceUserStatus::ACTIVE->value;
            $serviceUser->save();

            // Gán lại các meta_accounts (đã sync theo BM) cho service_user này
            if ($platform === PlatformType::META->value) {
                $bmIdsForCustomer = [];

                if (isset($newConfig['accounts']) && is_array($newConfig['accounts'])) {
                    foreach ($newConfig['accounts'] as $accountConfig) {
                        if (!isset($accountConfig['bm_ids']) || !is_array($accountConfig['bm_ids'])) {
                            continue;
                        }
                        foreach ($accountConfig['bm_ids'] as $rawBmId) {
                            $bmId = trim((string) $rawBmId);
                            if ($bmId !== '') {
                                $bmIdsForCustomer[] = $bmId;
                            }
                        }
                    }
                }

                if (empty($bmIdsForCustomer) && !empty($newConfig['bm_id'])) {
                    $bmIdsForCustomer[] = (string) $newConfig['bm_id'];
                }

                $bmIdsForCustomer = array_values(array_unique($bmIdsForCustomer));

                if (!empty($bmIdsForCustomer)) {
                    try {
                        // Gán service_user_id cho các meta_accounts thuộc các BM này mà hiện chưa có chủ
                        $updated = $this->metaAccountRepository->query()
                            ->whereIn('business_manager_id', $bmIdsForCustomer)
                            ->whereNull('service_user_id')
                            ->update(['service_user_id' => $serviceUser->id]);

                    } catch (\Throwable $attachError) {
                        Logging::error(
                            message: 'ServiceUserService@approveServiceUser: failed to attach existing meta accounts',
                            exception: $attachError
                        );
                    }
                }
            }

            // Gán lại các google_accounts (đã sync theo MCC) cho service_user này
            if ($platform === PlatformType::GOOGLE->value) {
                $mccIdsForCustomer = [];

                if (isset($newConfig['accounts']) && is_array($newConfig['accounts'])) {
                    foreach ($newConfig['accounts'] as $accountConfig) {
                        if (!isset($accountConfig['bm_ids']) || !is_array($accountConfig['bm_ids'])) {
                            continue;
                        }
                        foreach ($accountConfig['bm_ids'] as $rawMccId) {
                            $mccId = trim((string) $rawMccId);
                            if ($mccId !== '') {
                                $mccIdsForCustomer[] = $mccId;
                            }
                        }
                    }
                }

                if (empty($mccIdsForCustomer) && !empty($newConfig['google_manager_id'])) {
                    $mccIdsForCustomer[] = (string) $newConfig['google_manager_id'];
                }

                $mccIdsForCustomer = array_values(array_unique($mccIdsForCustomer));

                if (!empty($mccIdsForCustomer)) {
                    try {
                        // Gán service_user_id cho các google_accounts thuộc các MCC này mà hiện chưa có chủ
                        $updated = $this->googleAccountRepository->query()
                            ->whereIn('customer_manager_id', $mccIdsForCustomer)
                            ->whereNull('service_user_id')
                            ->update(['service_user_id' => $serviceUser->id]);
                    } catch (\Throwable $attachError) {
                        Logging::error(
                            message: 'ServiceUserService@approveServiceUser: failed to attach existing google accounts',
                            exception: $attachError
                        );
                    }
                }
            }

            // Dispatch job sync dữ liệu từ API sau khi approve thành công
            // Platform đã được load ở trên
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

                // Cập nhật status sang FAILED
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

            if (is_array($config['accounts']) && !empty($config['accounts'])) {
                $serviceUser->config_account = array_merge($currentConfig, array_filter([
                    'accounts' => $config['accounts'],
                    'payment_type' => $config['payment_type'] ?? null,
                ], fn($value) => $value !== null));
            } else {
                $serviceUser->config_account = array_merge($currentConfig, array_filter([
                    'meta_email' => $config['meta_email'] ?? null,
                    'display_name' => $config['display_name'] ?? null,
                    'bm_id' => $config['bm_id'] ?? null,
                    'uid' => $config['uid'] ?? null,
                    'account_name' => $config['account_name'] ?? null,
                    'timezone_bm' => $config['timezone_bm'] ?? null,
                    'info_fanpage' => $config['info_fanpage'] ?? null,
                    'info_website' => $config['info_website'] ?? null,
                    'payment_type' => $config['payment_type'] ?? null,
                ], fn($value) => $value !== null));
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

}
