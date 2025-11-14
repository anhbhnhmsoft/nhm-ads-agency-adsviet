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
        try {
            $now = now();
            
            // Tìm các transaction PENDING, DEPOSIT, có expires_at và đã quá hạn
            $expiredTransactions = $this->transactionRepository->query()
                ->where('status', WalletTransactionStatus::PENDING->value)
                ->where('type', WalletTransactionType::DEPOSIT->value)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', $now)
                ->get();

            if ($expiredTransactions->isEmpty()) {
                $this->info('Không có giao dịch nào cần hủy.');
                return Command::SUCCESS;
            }

            $count = 0;
            foreach ($expiredTransactions as $transaction) {
                $this->transactionRepository->updateById($transaction->id, [
                    'status' => WalletTransactionStatus::CANCELLED->value,
                    'description' => 'Tự động hủy do quá hạn thanh toán',
                ]);
                $count++;
            }

            Logging::web('ExpirePendingTransactions: Đã hủy ' . $count . ' giao dịch quá hạn');
            $this->info("Đã hủy {$count} giao dịch quá hạn.");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Logging::error('ExpirePendingTransactions error: ' . $e->getMessage(), exception: $e);
            $this->error('Lỗi khi hủy giao dịch quá hạn: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

