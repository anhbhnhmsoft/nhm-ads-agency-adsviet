<?php

namespace App\Common\Constants\Platform;

enum PlatformType: int
{
    case GOOGLE = 1;
    case META = 2;

    public function label()
    {
        return match ($this) {
            PlatformType::GOOGLE => __('enums.PlatformType.GOOGLE'),
            PlatformType::META => __('enums.PlatformType.META'),
        };
    }
}

