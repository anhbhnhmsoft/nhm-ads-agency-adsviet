<?php

namespace App\Console\Commands;

use App\Common\Constants\Wallet\WalletTransactionStatus;
use App\Common\Constants\Wallet\WalletTransactionType;
use App\Core\Logging;
use App\Repositories\UserWalletTransactionRepository;
use Illuminate\Console\Command;

class ExpirePendingTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tự động hủy các giao dịch nạp tiền đã quá hạn (expires_at)';

    public function __construct(
        protected UserWalletTransactionRepository $transactionRepository,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Tính năng tự động hủy giao dịch quá hạn đã được tắt.');
        return Command::SUCCESS;
    }
}

