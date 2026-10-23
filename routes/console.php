<?php

use App\Jobs\SyncAllPlatformsJob;
use Illuminate\Support\Facades\Schedule;

// Kiểm tra và xử lý giao dịch hết hạn mỗi 5 phút (Đã tắt theo yêu cầu không tự động hủy đơn)
// Schedule::command('transactions:expire')
//     ->everyFiveMinutes();

// Sync ads service user mỗi 5 phút để cập nhật spend sát hơn cho rule khóa creditline
Schedule::command('app:sync-ads-service-user')->everyFiveMinutes();

// Gửi cảnh báo ví thấp mỗi ngày lúc 09:00
Schedule::command('notifications:wallet-low-balance')->dailyAt('09:00');

// Tự động kiểm tra token/key nền tảng mỗi ngày
Schedule::command('platform-settings:check-tokens')->dailyAt('00:10');

// Fallback: Kiểm tra auto-pause accounts mỗi 30 phút (chính là webhook Meta push realtime)
Schedule::command('accounts:check-and-auto-pause')->everyThirtyMinutes();

// Billing spending fee theo ngưỡng chi tiêu & tự động pause campaign khi ví dưới 20 USD (chạy mỗi 5 phút)
Schedule::command('services:bill-postpay')->everyFiveMinutes()->withoutOverlapping();

// Khóa hạn mức gói creditline khi còn khoảng 20 USD
Schedule::command('services:enforce-creditline-limits')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// 1. Quét toàn bộ 332 BM & cập nhật tài khoản mới share toàn hệ thống (mỗi 15 phút, siêu nhẹ ~15s)
Schedule::job(new SyncAllPlatformsJob(syncInsights: false))->everyFifteenMinutes();

// 2. Cơ chế Delta Sync định kỳ: CHỈ cào 2 ngày (Hôm nay & Hôm qua) mỗi 30 phút, giảm 93% tải Meta API
Schedule::job(new SyncAllPlatformsJob(syncInsights: true, days: 2))->everyThirtyMinutes();

// 3. Cơ chế Deep Sync 30 ngày: Cào sâu toàn bộ lịch sử 1 lần duy nhất vào lúc 02:00 sáng khi không có người dùng
Schedule::job(new SyncAllPlatformsJob(syncInsights: true, days: 30))->dailyAt('02:00');

// routes/console.php
Schedule::command('app:calculate-spending-commission')->monthlyOn(1, '01:00');

// Calculate and payout cashback daily
Schedule::command('app:calculate-cashback')->dailyAt('03:00');
