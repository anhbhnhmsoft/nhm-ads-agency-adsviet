<?php

namespace App\Common\Constants\Payment;

enum PaymentStatus: string
{
    case CONFIRMED = "confirmed"; //Xác nhận
    case FINISHED = "finished"; //Hoàn thành
    case WAITING = "waiting"; //Chờ xử lý
    case CONFIRMING = "confirming"; //Đang xác nhận
    case FAILED = "failed"; //Thất bại
    case EXPIRED = "expired"; //Hết hạn
    case SENDING = "sending"; //Đang chuyển về ví cá nhân
    case PARTIALLY_PAID = "partially_paid"; //Khách thanh toán thiếu
}


