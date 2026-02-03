<?php

namespace App\Console\Commands;

use App\Core\Logging;
use App\Service\GoogleAdsNotificationService;
use Illuminate\Console\Command;
use App\Service\ConfigService;
use App\Common\Constants\Config\ConfigName;

class NotifyGoogleAdsLowBalance extends Command
{
    protected $signature = 'notifications:google-ads-low-balance';

    protected $description = 'Gửi thông báo Telegram/Email cho khách có Google Ads account balance thấp hơn ngưỡng đặt sẵn';

    public function __construct(
        protected GoogleAdsNotificationService $googleAdsNotificationService,
        protected ConfigService $configService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // Lấy ngưỡng tạm dừng từ cấu hình
        $threshold = (float) $this->configService->getValue(ConfigName::THRESHOLD_PAUSE, 100);
        $this->info(sprintf('Kiểm tra Google Ads accounts với ngưỡng %.2f USD ...', $threshold));

        // Debug: Đếm tổng số accounts có balance
        $totalAccountsWithBalance = \App\Models\GoogleAccount::whereNotNull('balance')->count();
        $this->line(sprintf('Tổng số Google Ads accounts có balance: %d', $totalAccountsWithBalance));

        // Debug: Đếm số accounts có balance <= threshold
        $accountsWithLowBalance = \App\Models\GoogleAccount::whereNotNull('balance')
            ->where('balance', '<=', $threshold)
            ->count();
        $this->line(sprintf('Số accounts có balance <= %.2f USD: %d', $threshold, $accountsWithLowBalance));

        $result = $this->googleAdsNotificationService->sendLowBalanceAlerts($threshold);

        if ($result->isError()) {
            $this->error($result->getMessage());
            return Command::FAILURE;
        }

        $data = $result->getData();
        $sent = $data['sent'] ?? 0;
        $found = $data['found'] ?? 0;
        $skipped = $data['skipped'] ?? 0;

        $this->line(sprintf('Tìm thấy %d accounts phù hợp', $found));
        $this->line(sprintf('Bỏ qua %d accounts (đã gửi hôm nay hoặc không có user/telegram/email)', $skipped));
        $message = sprintf('Đã gửi %d thông báo Google Ads balance thấp.', $sent);
        $this->info($message);

        Logging::web('NotifyGoogleAdsLowBalance', [
            'threshold' => $threshold,
            'total_accounts_with_balance' => $totalAccountsWithBalance,
            'accounts_with_low_balance' => $accountsWithLowBalance,
            'found' => $found,
            'skipped' => $skipped,
            'sent' => $sent,
        ]);

        return Command::SUCCESS;
    }
}

