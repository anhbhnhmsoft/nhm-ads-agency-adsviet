<?php

namespace App\Common\Constants\Config;

enum ConfigName: string
{
    case BEP20_WALLET_ADDRESS = 'BEP20_WALLET_ADDRESS';
    case TRC20_WALLET_ADDRESS = 'TRC20_WALLET_ADDRESS';
    case POSTPAY_MIN_BALANCE = 'POSTPAY_MIN_BALANCE';
    case THRESHOLD_PAUSE = 'THRESHOLD_PAUSE'; // ngưỡng tạm dừng khi balance thấp
}

