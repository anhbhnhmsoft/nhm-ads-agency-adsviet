<?php

namespace App\Console\Commands;

use App\Common\Constants\ServiceUser\ServiceUserStatus;
use App\Core\Logging;
use App\Repositories\GoogleAdsAccountInsightRepository;
use App\Repositories\MetaAdsAccountInsightRepository;
use App\Repositories\ServiceUserRepository;
use App\Service\CommissionService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculateSpendingCommission extends Command
{
    protected $signature = 'app:calculate-spending-commission';

    protected $description = 'Tính hoa hồng theo spending cho từng khách hàng trong một kỳ (tháng), dựa trên dữ liệu insights đã sync';

    public function __construct(
        protected ServiceUserRepository             $serviceUserRepository,
        protected MetaAdsAccountInsightRepository   $metaAdsAccountInsightRepository,
        protected GoogleAdsAccountInsightRepository $googleAdsAccountInsightRepository,
        protected CommissionService                 $commissionService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $time = Carbon::now()->format('Y-m');

        try {
            [$startDate, $endDate] = $this->getMonthRangeFromPeriod($time);
        } catch (\Throwable $e) {
            $this->error('Không thể xử lý kỳ tính hoa hồng spending cho tháng hiện tại.');
            return Command::FAILURE;
        }

        $this->info("Bắt đầu tính hoa hồng spending cho kỳ {$time} ({$startDate->toDateString()} - {$endDate->toDateString()})");

        // Lấy tất cả service_users ACTIVE, group theo customer
        $serviceUsers = $this->serviceUserRepository->query()
            ->where('status', ServiceUserStatus::ACTIVE->value)
            ->get(['id', 'user_id']);

        if ($serviceUsers->isEmpty()) {
            $this->info('Không có service user ACTIVE nào, kết thúc.');
            return Command::SUCCESS;
        }

        $customerIds = $serviceUsers->pluck('user_id')->unique()->values();

        $totalCustomers = 0;
        $totalWithCommission = 0;

        foreach ($customerIds as $customerId) {
            $totalCustomers++;

            // Lấy danh sách service_user_id của customer này
            $customerServiceUserIds = $serviceUsers
                ->where('user_id', $customerId)
                ->pluck('id')
                ->values()
                ->all();

            if (empty($customerServiceUserIds)) {
                continue;
            }

            $spendingAmount = $this->calculateTotalSpendingForServiceUsers(
                $customerServiceUserIds,
                $startDate,
                $endDate
            );

            if ($spendingAmount <= 0) {
                continue;
            }

            // Gọi CommissionService để ghi nhận hoa hồng
            $result = $this->commissionService->calculateSpendingCommission(
                (string) $customerId,
                $time,
                $spendingAmount
            );

            if ($result->isError()) {
                $this->error("❌ Lỗi tính hoa hồng spending cho customer {$customerId}: " . $result->getMessage());
                Logging::error(
                    message: 'CalculateSpendingCommission: Failed to calculate commission for customer',
                    context: [
                        'customer_id' => $customerId,
                        'period' => $time,
                        'spending_amount' => $spendingAmount,
                        'error' => $result->getMessage(),
                    ]
                );
                continue;
            }

            $totalWithCommission++;

            $this->info("✓ Đã tính hoa hồng spending cho customer {$customerId}, spending={$spendingAmount}");
        }

        $this->info("Hoàn thành. Tổng customer xem xét: {$totalCustomers}, có hoa hồng: {$totalWithCommission}");

        return Command::SUCCESS;
    }

    /**
     * Tính tổng spending (Meta + Google) cho danh sách service_user_id trong khoảng ngày.
     */
    private function calculateTotalSpendingForServiceUsers(array $serviceUserIds, Carbon $startDate, Carbon $endDate): float
    {
        // Meta
        $metaTotal = (float) $this->metaAdsAccountInsightRepository->query()
            ->whereIn('service_user_id', $serviceUserIds)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->sum('spend');

        // Google
        $googleTotal = (float) $this->googleAdsAccountInsightRepository->query()
            ->whereIn('service_user_id', $serviceUserIds)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->sum('spend');

        return $metaTotal + $googleTotal;
    }

    /**
     * Chuyển time dạng YYYY-MM thành khoảng ngày đầu tháng / cuối tháng.
     */
    private function getMonthRangeFromPeriod(string $period): array
    {
        [$year, $month] = explode('-', $period);

        $start = Carbon::createFromDate((int) $year, (int) $month, 1)->startOfDay();
        $end   = (clone $start)->endOfMonth()->endOfDay();

        return [$start, $end];
    }
}


