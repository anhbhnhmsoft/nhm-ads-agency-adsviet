<?php

use Illuminate\Support\Facades\Schedule;

// Kiểm tra và xử lý giao dịch hết hạn mỗi 5 phút
Schedule::command('transactions:expire')
    ->everyFiveMinutes();

// Sync ads service user mỗi giờ
Schedule::command('app:sync-ads-service-user')->hourly();

// Gửi cảnh báo ví thấp mỗi ngày lúc 09:00
Schedule::command('notifications:wallet-low-balance')->dailyAt('09:00');

// Kiểm tra và auto-pause accounts nếu spending > balance + 100 mỗi 10 phút
Schedule::command('accounts:check-and-auto-pause')->everyTenMinutes();

// Billing postpay hằng ngày (02:00)
Schedule::command('services:bill-postpay')->dailyAt('02:00');

// routes/console.php
Schedule::command('app:calculate-spending-commission')->monthlyOn(1, '01:00');