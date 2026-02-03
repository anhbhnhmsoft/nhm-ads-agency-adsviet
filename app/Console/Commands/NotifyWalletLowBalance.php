<?php

namespace App\Console\Commands;

use App\Core\Logging;
use App\Service\WalletNotificationService;
use Illuminate\Console\Command;
use App\Common\Constants\Config\ConfigName;
use App\Service\ConfigService;


class NotifyWalletLowBalance extends Command
{
    protected $signature = 'notifications:wallet-low-balance';

    protected $description = 'Gửi thông báo Telegram cho khách có số dư ví thấp hơn ngưỡng đặt sẵn';

    public function __construct(
        protected WalletNotificationService $walletNotificationService,
        protected ConfigService $configService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // Lấy ngưỡng tạm dừng từ cấu hình
        $threshold = (float) $this->configService->getValue(ConfigName::THRESHOLD_PAUSE, 100);

        $this->info(sprintf('Kiểm tra ví với ngưỡng %.2f USDT ...', $threshold));
        $result = $this->walletNotificationService->sendLowBalanceAlerts($threshold);

        if ($result->isError()) {
            $this->error($result->getMessage());
            return Command::FAILURE;
        }

        $data = $result->getData();
        $sent = $data['sent'] ?? 0;
        $message = sprintf('Đã gửi %d thông báo ví thấp.', $sent);
        $this->info($message);
        Logging::web('NotifyWalletLowBalance', [
            'threshold' => $threshold,
            'sent' => $sent,
        ]);

        return Command::SUCCESS;
    }
}

