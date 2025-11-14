<?php

namespace App\Common\Constants\ServiceUser;

enum ServiceUserStatus: int
{

    /**
     * Mô tả luồng
     * - Khi khách hàng thanh toán xong thì mới khởi tạo dich vụ với trạng thái PENDING
     * - Mặc định là PENDING
     *
     * -> Sau 1 tiếng mà không thấy admin system xử lý (để sau)
     *      -> chuyển sang trạng thái QUEUE_JOB_PENDING để job chạy tự động tạo, và khi khởi tạo thì sẽ nhảy sang QUEUE_JOB_ON_PROCESS
     *      -> Job chạy thành công -> chuyển sang ACTIVE
     *      -> Job chạy thất bại -> chuyển sang FAILED
     * -> User kích hoạt dịch vụ thủ công -> tự chuyển tay sang trạng thái PROCESSING
     *      -> Kích hoạt thành công -> chuyển sang ACTIVE
     *      -> Kích hoạt thất bại -> chuyển sang FAILED
     */
    case PENDING = 1;    // Chờ kích hoạt
    case QUEUE_JOB_PENDING = 2; // Đang chờ xử lý (chờ Job chạy)
    case QUEUE_JOB_ON_PROCESS = 3;  // Đang xử lý (Job đang chạy)
    case PROCESSING = 5; // Đang xử lý kích hoạt (tự động bằng tay)
    case ACTIVE = 6;     // Đang hoạt động
    case FAILED = 7;     // Kích hoạt tự động thất bại
    case CANCELLED = 8;  // Đã hủy
}
