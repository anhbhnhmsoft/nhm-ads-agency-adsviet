<?php

namespace App\Console\Commands;

use App\Core\Logging;
use App\Service\WalletNotificationService;
use Illuminate\Console\Command;

class NotifyWalletLowBalance extends Command
{
    protected $signature = 'notifications:wallet-low-balance {--amount=100 : Ngưỡng cảnh báo theo USDT}';

    protected $description = 'Gửi thông báo Telegram cho khách có số dư ví thấp hơn ngưỡng đặt sẵn';

    public function __construct(
        protected WalletNotificationService $walletNotificationService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $threshold = (float) $this->option('amount');

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

