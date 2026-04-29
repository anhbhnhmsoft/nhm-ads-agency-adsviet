<?php

namespace App\Common\Constants\ServicePackage;

enum ServicePackagePaymentType: string
{
    case PREPAY = 'prepay';
    case POSTPAY = 'postpay';

    public static function getValues(): array
    {
        return array_map(
            fn (self $case) => $case->value,
            self::cases(),
        );
    }
}

