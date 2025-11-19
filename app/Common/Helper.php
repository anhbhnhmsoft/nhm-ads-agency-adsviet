<?php

namespace App\Common;

use App\Common\Constants\User\UserRole;
use Illuminate\Support\Str;

class Helper
{

    public static function generateReferCodeUser(UserRole $role): string
    {
        $fix = match ($role) {
            UserRole::ADMIN => 'ADM-',
            UserRole::MANAGER => 'MNG-',
            UserRole::EMPLOYEE => 'EMP-',
            UserRole::AGENCY => 'AGY-',
            UserRole::CUSTOMER => 'CST-',
        };
        return $fix . self::generateReferCode();
    }

    public static function generateReferCode(): string
    {
        return strtoupper(substr(Str::uuid()->toString(), 0, 8));
    }

    public static function getWebDeviceId(): string
    {
        return sha1(request()->userAgent() . request()->ip());
    }

    public static function generateTokenRandom(): string
    {
        return Str::random(60);
    }

    /**
     * Tính toán phần trăm thay đổi giữa hai giá trị
     * @param float $previous Giá trị trước đó
     * @param float $current Giá trị hiện tại
     * @return float Phần trăm thay đổi
     */
    public static function calculatePercentageChange($previous, $current): float
    {
        if ($previous == 0) {
            if ($current > 0) {
                return 100.0; // Tăng 100% nếu hôm qua là 0
            }
            return 0.0; // Không thay đổi (0 so với 0)
        }

        $change = (($current - $previous) / $previous) * 100;
        return round($change, 1);
    }
}
