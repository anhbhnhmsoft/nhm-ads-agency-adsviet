<?php

namespace App\Service;

use App\Common\Constants\User\UserRole;
use App\Common\Constants\ServiceUser\ServiceUserStatus;
use App\Core\Logging;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Models\EmployeeCommission;
use App\Repositories\CommissionTransactionRepository;
use App\Repositories\EmployeeCommissionRepository;
use App\Repositories\UserReferralRepository;
use App\Repositories\UserRepository;
use App\Repositories\ServicePackageRepository;
use App\Repositories\ServiceUserRepository;

class CommissionService
{
    public function __construct(
        protected EmployeeCommissionRepository $employeeCommissionRepository,
        protected CommissionTransactionRepository $commissionTransactionRepository,
        protected UserReferralRepository $userReferralRepository,
        protected UserRepository $userRepository,
        protected ServicePackageRepository $servicePackageRepository,
        protected ServiceUserRepository $serviceUserRepository,
    ) {
    }

    /**
     * Lấy danh sách cấu hình hoa hồng
     */
    public function getListCommissions(QueryListDTO $queryListDTO): ServiceReturn
    {
        try {
            $filter = $queryListDTO->filter ?? [];
            $query = $this->employeeCommissionRepository->filterQuery(
                $this->employeeCommissionRepository->query(),
                $filter
            );
            $query = $this->employeeCommissionRepository->sortQuery(
                $query,
                $queryListDTO->sortBy,
                $queryListDTO->sortDirection
            );
            $query->with(['employee', 'servicePackage']);

            $paginator = $query->paginate($queryListDTO->perPage, ['*'], 'page', $queryListDTO->page);
            return ServiceReturn::success(data: $paginator);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'CommissionService@getListCommissions error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Lấy danh sách gói dịch vụ để cấu hình hoa hồng
     */
    public function getServicePackages(): ServiceReturn
    {
        try {
            $query = $this->servicePackageRepository->query()
                ->where('disabled', false)
                ->select('id', 'name', 'platform')
                ->orderBy('name');

            $packages = $query->get();

            return ServiceReturn::success(data: $packages);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'CommissionService@getServicePackages error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Tạo cấu hình hoa hồng
     */
    public function createCommission(array $data): ServiceReturn
    {
        try {
            $commission = $this->employeeCommissionRepository->create($data);
            return ServiceReturn::success(data: $commission);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'CommissionService@createCommission error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Lấy cấu hình hoa hồng theo id
     */
    public function getCommissionById(string $id): ServiceReturn
    {
        try {
            $commission = $this->employeeCommissionRepository->query()
                ->with(['employee', 'servicePackage'])
                ->find($id);
            if (!$commission) {
                return ServiceReturn::error(message: __('common_error.not_found'));
            }
            return ServiceReturn::success(data: $commission);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'CommissionService@getCommissionById error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Cập nhật cấu hình hoa hồng
     */
    public function updateCommission(string $id, array $data): ServiceReturn
    {
        try {
            $commission = $this->employeeCommissionRepository->query()->find($id);
            if (!$commission) {
                return ServiceReturn::error(message: __('common_error.not_found'));
            }
            $commission->update($data);
            return ServiceReturn::success(data: $commission);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'CommissionService@updateCommission error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Xóa cấu hình hoa hồng
     */
    public function deleteCommission(string $id): ServiceReturn
    {
        try {
            $commission = $this->employeeCommissionRepository->query()->find($id);
            if (!$commission) {
                return ServiceReturn::error(message: __('common_error.not_found'));
            }
            $commission->delete();
            return ServiceReturn::success();
        } catch (\Exception $exception) {
            Logging::error(
                message: 'CommissionService@deleteCommission error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Tính hoa hồng dịch vụ khi khách mua dịch vụ
     * Logic mới: Lấy hoa hồng từ cấu hình của gói dịch vụ, không phải từ nhân viên
     */
    public function calculateServiceCommission(string $serviceUserId, float $serviceAmount): ServiceReturn
    {
        try {
            $serviceUser = $this->serviceUserRepository->query()
                ->with(['user', 'package'])
                ->find($serviceUserId);
            if (!$serviceUser) {
                return ServiceReturn::error(message: 'Service user not found');
            }

            $customerId = $serviceUser->user_id;
            $packageId = $serviceUser->package_id;
            
            if (!$packageId) {
                return ServiceReturn::success();
            }

            $employeeId = $this->getEmployeeIdForCustomer($customerId);
            if (!$employeeId) {
                return ServiceReturn::success(); // Không có employee quản lý, không tính hoa hồng
            }

            // Lấy cấu hình hoa hồng dịch vụ từ package
            $commissionConfig = $this->employeeCommissionRepository->getActiveCommissionByPackageAndType(
                $packageId,
                EmployeeCommission::TYPE_SERVICE
            );
            if (!$commissionConfig) {
                return ServiceReturn::success();
            }

            // Tính hoa hồng
            $commissionAmount = $serviceAmount * ($commissionConfig->rate / 100);

            // Lưu transaction hoa hồng
            $transaction = $this->commissionTransactionRepository->create([
                'employee_id' => $employeeId,
                'customer_id' => $customerId,
                'type' => EmployeeCommission::TYPE_SERVICE,
                'reference_type' => 'ServiceUser',
                'reference_id' => $serviceUserId,
                'base_amount' => $serviceAmount,
                'commission_rate' => $commissionConfig->rate,
                'commission_amount' => $commissionAmount,
                'period' => now()->format('Y-m'),
            ]);

            return ServiceReturn::success(data: $transaction);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'CommissionService@calculateServiceCommission error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Tính hoa hồng theo spending của khách hàng (theo tháng)
     */
    public function calculateSpendingCommission(
        string $customerId,
        string $period,
        float $spendingAmount
    ): ServiceReturn {
        try {
            $employeeId = $this->getEmployeeIdForCustomer($customerId);
            if (!$employeeId) {
                return ServiceReturn::success();
            }

            // Lấy package từ service user của customer
            $serviceUser = $this->serviceUserRepository->query()
                ->where('user_id', $customerId)
                ->where('status', ServiceUserStatus::ACTIVE->value)
                ->with('package')
                ->first();
            
            if (!$serviceUser || !$serviceUser->package_id) {
                return ServiceReturn::success();
            }

            // Lấy cấu hình hoa hồng spending từ package
            $commissionConfig = $this->employeeCommissionRepository->getActiveCommissionByPackageAndType(
                $serviceUser->package_id,
                EmployeeCommission::TYPE_SPENDING
            );
            if (!$commissionConfig) {
                return ServiceReturn::success();
            }

            // Kiểm tra min/max amount
            if ($commissionConfig->min_amount && $spendingAmount < $commissionConfig->min_amount) {
                return ServiceReturn::success(); // Spending quá thấp
            }
            if ($commissionConfig->max_amount && $spendingAmount > $commissionConfig->max_amount) {
                $spendingAmount = $commissionConfig->max_amount; // Chỉ tính đến max
            }

            $commissionAmount = $spendingAmount * ($commissionConfig->rate / 100);

            // Kiểm tra xem đã tính hoa hồng cho period này chưa
            $existing = $this->commissionTransactionRepository->query()
                ->where('employee_id', $employeeId)
                ->where('customer_id', $customerId)
                ->where('type', EmployeeCommission::TYPE_SPENDING)
                ->where('period', $period)
                ->first();

            if ($existing) {
                // Cập nhật lại nếu spending thay đổi
                $existing->update([
                    'base_amount' => $spendingAmount,
                    'commission_amount' => $commissionAmount,
                ]);
                return ServiceReturn::success(data: $existing);
            }

            $transaction = $this->commissionTransactionRepository->create([
                'employee_id' => $employeeId,
                'customer_id' => $customerId,
                'type' => EmployeeCommission::TYPE_SPENDING,
                'reference_type' => 'Customer',
                'reference_id' => $customerId,
                'base_amount' => $spendingAmount,
                'commission_rate' => $commissionConfig->rate,
                'commission_amount' => $commissionAmount,
            'period' => $period,
            ]);

            return ServiceReturn::success(data: $transaction);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'CommissionService@calculateSpendingCommission error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Tính hoa hồng bán account (theo phí mở tài khoản * số tài khoản)
     */
    public function calculateAccountCommission(string $serviceUserId, int $accountsCount, float $openFee): ServiceReturn
    {
        try {
            if ($accountsCount <= 0 || $openFee <= 0) {
                return ServiceReturn::success();
            }

            $serviceUser = $this->serviceUserRepository->query()
                ->with(['user', 'package'])
                ->find($serviceUserId);
            if (!$serviceUser) {
                return ServiceReturn::error(message: 'Service user not found');
            }

            $customerId = $serviceUser->user_id;
            $packageId  = $serviceUser->package_id;

            if (!$packageId) {
                return ServiceReturn::success();
            }

            $employeeId = $this->getEmployeeIdForCustomer($customerId);
            if (!$employeeId) {
                return ServiceReturn::success();
            }

            // Lấy cấu hình hoa hồng bán account từ package
            $commissionConfig = $this->employeeCommissionRepository->getActiveCommissionByPackageAndType(
                $packageId,
                EmployeeCommission::TYPE_ACCOUNT
            );
            if (!$commissionConfig) {
                return ServiceReturn::success();
            }

            $baseAmount       = $openFee * $accountsCount;
            $commissionAmount = $baseAmount * ($commissionConfig->rate / 100);

            $transaction = $this->commissionTransactionRepository->create([
                'employee_id'        => $employeeId,
                'customer_id'        => $customerId,
                'type'               => EmployeeCommission::TYPE_ACCOUNT,
                'reference_type'     => 'ServiceUser',
                'reference_id'       => $serviceUserId,
                'base_amount'        => $baseAmount,
                'commission_rate'    => $commissionConfig->rate,
                'commission_amount'  => $commissionAmount,
            'period'             => now()->format('Y-m'),
            ]);

            return ServiceReturn::success(data: $transaction);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'CommissionService@calculateAccountCommission error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Lấy danh sách hoa hồng để chốt lương
     */
    public function getCommissionReport(QueryListDTO $queryListDTO): ServiceReturn
    {
        try {
            $filter = $queryListDTO->filter ?? [];
            $query = $this->commissionTransactionRepository->filterQuery(
                $this->commissionTransactionRepository->query(),
                $filter
            );
            $query = $this->commissionTransactionRepository->sortQuery(
                $query,
                $queryListDTO->sortBy,
                $queryListDTO->sortDirection
            );
            $query->with(['employee', 'customer']);

            $paginator = $query->paginate($queryListDTO->perPage, ['*'], 'page', $queryListDTO->page);
            return ServiceReturn::success(data: $paginator);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'CommissionService@getCommissionReport error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Tổng hợp hoa hồng theo nhân viên/quản lý
     */
    public function getCommissionSummaryByEmployee(array $filter = []): ServiceReturn
    {
        try {
            $query = $this->commissionTransactionRepository->filterQuery(
                $this->commissionTransactionRepository->query()->with('employee'),
                $filter
            );

            $summary = $query
                ->selectRaw('employee_id, SUM(base_amount::numeric) as total_base_amount, SUM(commission_amount::numeric) as total_commission_amount')
                ->groupBy('employee_id')
                ->get();

            return ServiceReturn::success(data: $summary);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'CommissionService@getCommissionSummaryByEmployee error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Chốt lương (đánh dấu hoa hồng đã thanh toán)
     */
    public function markCommissionsAsPaid(array $ids, ?string $paidAt = null): ServiceReturn
    {
        try {
            $count = $this->commissionTransactionRepository->markAsPaid($ids, $paidAt);
            return ServiceReturn::success(data: ['updated' => $count]);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'CommissionService@markCommissionsAsPaid error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Lấy employee_id quản lý customer (từ referral)
     */
    protected function getEmployeeIdForCustomer(string $customerId): ?string
    {
        $referral = $this->userReferralRepository->query()
            ->where('referred_id', $customerId)
            ->whereNull('deleted_at')
            ->first();

        if (!$referral) {
            return null;
        }

        $employee = $this->userRepository->find($referral->referrer_id);
        if (!$employee) {
            return null;
        }

        // Chỉ trả về nếu là EMPLOYEE hoặc MANAGER
        if (in_array($employee->role, [UserRole::EMPLOYEE->value, UserRole::MANAGER->value])) {
            return $employee->id;
        }

        return null;
    }
}

