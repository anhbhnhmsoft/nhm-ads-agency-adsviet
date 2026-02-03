<?php

namespace App\Console\Commands;

use App\Common\Constants\Config\ConfigName;
use App\Core\Logging;
use App\Service\ConfigService;
use App\Service\GoogleAdsService;
use App\Service\MetaService;
use Illuminate\Console\Command;

class CheckAndAutoPauseAccounts extends Command
{
    protected $signature = 'accounts:check-and-auto-pause';

    protected $description = 'Kiểm tra và tự động tạm dừng tài khoản nếu spending > balance + threshold';

    public function __construct(
        protected GoogleAdsService $googleAdsService,
        protected MetaService $metaService,
        protected ConfigService $configService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // Lấy ngưỡng tạm dừng từ cấu hình
        $threshold = (float) $this->configService->getValue(ConfigName::THRESHOLD_PAUSE, 100);
        $this->info(sprintf('Kiểm tra và auto-pause accounts với ngưỡng %.2f USD ...', $threshold));
        // Google Ads
        $this->line('Đang kiểm tra Google Ads accounts...');
        $googleResult = $this->googleAdsService->checkAndAutoPauseAccounts($threshold);
        if ($googleResult->isError()) {
            $this->error('Google Ads: ' . $googleResult->getMessage());
        } else {
            $googleData = $googleResult->getData();
            $this->line(sprintf('Google Ads: Đã pause %d accounts, gửi %d thông báo, %d lỗi',
                $googleData['paused'] ?? 0,
                $googleData['notified'] ?? 0,
                $googleData['errors'] ?? 0
            ));
        }

        // Meta Ads
        $this->line('Đang kiểm tra Meta Ads accounts...');
        $metaResult = $this->metaService->checkAndAutoPauseAccounts($threshold);
        if ($metaResult->isError()) {
            $this->error('Meta Ads: ' . $metaResult->getMessage());
        } else {
            $metaData = $metaResult->getData();
            $this->line(sprintf('Meta Ads: Đã pause %d accounts, gửi %d thông báo, %d lỗi',
                $metaData['paused'] ?? 0,
                $metaData['notified'] ?? 0,
                $metaData['errors'] ?? 0
            ));
        }

        $totalPaused = ($googleData['paused'] ?? 0) + ($metaData['paused'] ?? 0);
        $totalNotified = ($googleData['notified'] ?? 0) + ($metaData['notified'] ?? 0);
        $totalErrors = ($googleData['errors'] ?? 0) + ($metaData['errors'] ?? 0);

        $this->info(sprintf('Tổng kết: Đã pause %d accounts, gửi %d thông báo, %d lỗi',
            $totalPaused,
            $totalNotified,
            $totalErrors
        ));

        Logging::web('CheckAndAutoPauseAccounts', [
            'threshold' => $threshold,
            'google' => $googleData ?? null,
            'meta' => $metaData ?? null,
            'total_paused' => $totalPaused,
            'total_notified' => $totalNotified,
            'total_errors' => $totalErrors,
        ]);

        return Command::SUCCESS;
    }
}

