<?php

namespace App\Common\Constants\NotificationType;

enum NotificationType: int
{
    case OTP = 1;
    case TICKET = 2;
    case GOOGLE_ADS = 3;
    case META_ADS = 4;
    case WALLET = 5;
}
