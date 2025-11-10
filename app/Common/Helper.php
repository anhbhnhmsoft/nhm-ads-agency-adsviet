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
}
