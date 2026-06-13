<?php

namespace App\Console\Commands;

use App\Models\PlatformSetting;
use App\Service\PlatformTokenHealthService;
use Illuminate\Console\Command;

class CheckPlatformTokens extends Command
{
    protected $signature = 'platform-settings:check-tokens {--all : Kiểm tra cả cấu hình đã tắt}';

    protected $description = 'Tự động kiểm tra token/key của các cấu hình nền tảng';

    public function handle(PlatformTokenHealthService $tokenHealthService): int
    {
        $query = PlatformSetting::query()->orderBy('id');

        if (!$this->option('all')) {
            $query->where('disabled', false);
        }

        $settings = $query->get();

        if ($settings->isEmpty()) {
            $this->info('Không có cấu hình nền tảng nào cần kiểm tra.');
            return self::SUCCESS;
        }

        $checked = 0;

        foreach ($settings as $setting) {
            $config = (array) ($setting->config ?? []);
            $tokenStatus = $tokenHealthService->check((int) $setting->platform, $config);

            $config['token_status'] = $tokenStatus;
            $setting->update(['config' => $config]);

            $checked++;
            $this->line(sprintf(
                '%s | %s | %s',
                $setting->name ?: $setting->id,
                $tokenStatus['expires_label'] ?? 'Chưa xác định',
                $tokenStatus['message'] ?? ''
            ));
        }

        $this->info("Đã kiểm tra {$checked} cấu hình nền tảng.");

        return self::SUCCESS;
    }
}
