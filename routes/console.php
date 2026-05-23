<?php

use App\Jobs\SyncAllPlatformsJob;
use Illuminate\Support\Facades\Schedule;

// Kiểm tra và xử lý giao dịch hết hạn mỗi 5 phút
Schedule::command('transactions:expire')
    ->everyFiveMinutes();

// Sync ads service user mỗi 30 phút
Schedule::command('app:sync-ads-service-user')->everyThirtyMinutes();

// Gửi cảnh báo ví thấp mỗi ngày lúc 09:00
Schedule::command('notifications:wallet-low-balance')->dailyAt('09:00');

// Kiểm tra và auto-pause accounts nếu balance dương và vượt ngưỡng mỗi 2 phút
Schedule::command('accounts:check-and-auto-pause')->everyTwoMinutes();

// Billing postpay hằng ngày (02:00)
Schedule::command('services:bill-postpay')->dailyAt('02:00');

// Đồng bộ toàn bộ các Platform (BM+MCC) mỗi 30 phút
Schedule::job(SyncAllPlatformsJob::class)->everyThirtyMinutes();

// routes/console.php
Schedule::command('app:calculate-spending-commission')->monthlyOn(1, '01:00');

// Calculate and payout cashback daily
Schedule::command('app:calculate-cashback')->dailyAt('03:00');
