<?php

namespace App\Console\Commands;

use App\Core\Logging;
use App\Models\MetaAccount;
use App\Service\MetaAdsNotificationService;
use Illuminate\Console\Command;
use App\Common\Constants\Config\ConfigName;
use App\Service\ConfigService;


class NotifyMetaAdsLowBalance extends Command
{
    protected $signature = 'notifications:meta-ads-low-balance';

    protected $description = 'Gửi thông báo Telegram/Email cho khách có Meta Ads account cạn tiền';

    public function __construct(
        protected MetaAdsNotificationService $metaAdsNotificationService,
        protected ConfigService $configService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // Lấy ngưỡng tạm dừng từ cấu hình
        $threshold = (float) $this->configService->getValue(ConfigName::THRESHOLD_PAUSE, 100);

        $this->info(sprintf('Kiểm tra Meta Ads accounts với ngưỡng %.2f ...', $threshold));

        $totalAccounts = MetaAccount::count();
        $this->line(sprintf('Tổng số Meta Ads accounts: %d', $totalAccounts));

        $result = $this->metaAdsNotificationService->sendLowBalanceAlerts($threshold);

        if ($result->isError()) {
            $this->error($result->getMessage());
            return Command::FAILURE;
        }

        $data = $result->getData();
        $sent = $data['sent'] ?? 0;
        $found = $data['found'] ?? 0;
        $skipped = $data['skipped'] ?? 0;

        $this->line(sprintf('Tìm thấy %d accounts phù hợp', $found));
        $this->line(sprintf('Bỏ qua %d accounts (đã gửi hôm nay hoặc không có liên hệ)', $skipped));
        $this->info(sprintf('Đã gửi %d thông báo Meta Ads balance thấp.', $sent));

        Logging::web('NotifyMetaAdsLowBalance', [
            'threshold' => $threshold,
            'total_accounts' => $totalAccounts,
            'found' => $found,
            'skipped' => $skipped,
            'sent' => $sent,
        ]);

        return Command::SUCCESS;
    }
}

