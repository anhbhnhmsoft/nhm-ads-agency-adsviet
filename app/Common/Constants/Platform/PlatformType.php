<?php

namespace App\Common\Constants\Platform;

enum PlatformType: int
{
    case GOOGLE = 1;
    case META = 2;

    public function label()
    {
        return match ($this) {
            PlatformType::GOOGLE => __('enum.PlatformType.GOOGLE'),
            PlatformType::META => __('enum.PlatformType.META'),
        };
    }

    public static function getValues(): array
    {
        return [
            PlatformType::GOOGLE->value,
            PlatformType::META->value,
        ];
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}

