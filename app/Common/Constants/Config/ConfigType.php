<?php

namespace App\Common\Constants\Config;

enum ConfigType: int
{
    case IMAGE = 1;
    case STRING = 2;

    public static function getOptions(): array
    {
        return [
            self::IMAGE->value => __('constants.config_type.image'),
            self::STRING->value => __('constants.config_type.string'),
        ];
    }

    public function getLabel(): string
    {
        return self::getOptions()[$this->value] ?? '';
    }
}

