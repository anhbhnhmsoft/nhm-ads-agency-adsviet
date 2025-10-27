<?php

namespace App\Core\GenerateId;

class Snowflake
{
    private static int $epoch = 1700000000000;
    private static int $machineId;
    public static function init(): void
    {
        self::$machineId = (int) env('MACHINE_ID', 1);
    }
    public static function id(): int
    {
        if (!isset(self::$machineId)) {
            self::init();
        }

        $time = (int) (microtime(true) * 1000) - self::$epoch;
        $machine = self::$machineId & 0x3FF; // 10 bits
        $random = mt_rand(0, 0x3FF);          // 10 bits

        return ($time << 20) | ($machine << 10) | $random;
    }
}
