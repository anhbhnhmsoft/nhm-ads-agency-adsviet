<?php

use Illuminate\Support\Facades\Schedule;

// Kiểm tra và xử lý giao dịch hết hạn mỗi 5 phút
Schedule::command('transactions:expire')
    ->everyFiveMinutes();

// Sync ads service user mỗi giờ
Schedule::command('app:sync-ads-service-user')->hourly();

// Gửi cảnh báo ví thấp mỗi ngày lúc 09:00
Schedule::command('notifications:wallet-low-balance')->dailyAt('09:00');
