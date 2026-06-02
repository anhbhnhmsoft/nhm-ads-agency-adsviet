<?php

namespace App\Common\Constants\ServicePackage;

enum AccountBillingSource: string
{
    case CUSTOMER_CARD = 'customer_card';
    case ADVIET_CARD = 'adviet_card';
    case SUPPLIER_CREDIT_LINE = 'supplier_credit_line';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
