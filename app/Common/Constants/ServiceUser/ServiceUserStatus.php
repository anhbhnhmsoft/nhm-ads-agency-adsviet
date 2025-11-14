<?php

namespace App\Common\Constants\ServiceUser;

enum ServiceUserStatus: int
{
    case PENDING = 1;    // Chờ kích hoạt
    case QUEUE_JOB_PENDING = 2; // Đang chờ xử lý (chờ Job chạy)
    case QUEUE_JOB_ON_PROCESS = 3;  // Đang xử lý (Job đang chạy)
    case ACTIVE = 4;     // Đang hoạt động
    case FAILED = 5;     // Kích hoạt tự động thất bại
    case CANCELLED = 6;  // Đã hủy
}
