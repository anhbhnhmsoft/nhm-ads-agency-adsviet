<?php

namespace App\Common\Constants\ServicePackage;

enum ServiceAccountInventoryStatus: string
{
    case AVAILABLE = 'available';
    case RESERVED = 'reserved';
    case ASSIGNED = 'assigned';
    case FAILED = 'failed';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
