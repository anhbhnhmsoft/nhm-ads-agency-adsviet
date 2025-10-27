<?php

namespace App\Core\GenerateId;

trait GenerateIdSnowflake
{
    protected static function bootGenerateIdTimestamp(): void
    {
        static::creating(function ($model) {
            if (!$model->getKey()) {
                $model->id = Snowflake::id();
            }
        });
    }
}
