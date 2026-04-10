<?php

namespace App\Console\Commands;

use App\Service\CashbackService;
use Illuminate\Console\Command;

class CalculateCashback extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:calculate-cashback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and payout cashback for service users every 30 days based on their spending.';

    public function __construct(
        protected CashbackService $cashbackService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cashback calculation...');

        $this->cashbackService->processAllActiveServices();

        $this->info('Cashback calculation completed.');
    }
}
