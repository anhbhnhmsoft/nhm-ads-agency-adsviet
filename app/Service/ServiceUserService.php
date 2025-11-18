<?php

namespace App\Service;

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
        protected UserWalletTransactionRepository $walletTransactionRepository
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
            $serviceUser = $this->serviceUserRepository->find($id);
            if (!$serviceUser) {
                return ServiceReturn::error(message: __('common_error.not_found'));
            }

            // Merge config_account hiện tại với config mới
            $currentConfig = $serviceUser->config_account ?? [];
            if (!is_array($currentConfig)) {
                $currentConfig = [];
            }

            $serviceUser->config_account = array_merge($currentConfig, [
                'meta_email' => $config['meta_email'] ?? ($currentConfig['meta_email'] ?? ''),
                'display_name' => $config['display_name'] ?? ($currentConfig['display_name'] ?? ''),
                'bm_id' => $config['bm_id'] ?? ($currentConfig['bm_id'] ?? ''),
                'uid' => $config['uid'] ?? ($currentConfig['uid'] ?? null),
                'account_name' => $config['account_name'] ?? ($currentConfig['account_name'] ?? null),
            ]);
            $serviceUser->status = \App\Common\Constants\ServiceUser\ServiceUserStatus::ACTIVE->value;
            $serviceUser->save();

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

    // Cập nhật config_account của đơn dịch vụ (để bổ sung BM ID sau)
    public function updateConfigAccount(string $id, array $config): ServiceReturn
    {
        try {
            $serviceUser = $this->serviceUserRepository->find($id);
            if (!$serviceUser) {
                return ServiceReturn::error(message: __('common_error.not_found'));
            }

            $currentConfig = $serviceUser->config_account ?? [];
            if (!is_array($currentConfig)) {
                $currentConfig = [];
            }

            $serviceUser->config_account = array_merge($currentConfig, array_filter([
                'meta_email' => $config['meta_email'] ?? null,
                'display_name' => $config['display_name'] ?? null,
                'bm_id' => $config['bm_id'] ?? null,
                'uid' => $config['uid'] ?? null,
                'account_name' => $config['account_name'] ?? null,
            ], fn($value) => $value !== null));
            $serviceUser->save();

            return ServiceReturn::success(data: $serviceUser);
        } catch (\Throwable $e) {
            Logging::error(
                message: 'ServiceUserService@updateConfigAccount error: '.$e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

}
