<?php

namespace App\Common\Constants\MetaBusinessManager;

class MetaBusinessManagerSource
{
    public const SELF = 'self';
    public const CONFIGURED = 'configured';
    public const RELATED = 'related';

    public static function directSources(): array
    {
        return [
            self::SELF,
            self::CONFIGURED,
        ];
    }
}
