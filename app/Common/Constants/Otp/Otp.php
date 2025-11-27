<?php

namespace App\Common\Constants\Otp;

enum Otp: int
{
    case VERIFY = 1;
    case EMAIL_VERIFICATION = 2;
    case FORGOT_PASSWORD = 3;
}
