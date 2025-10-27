<?php

namespace App\Common\Constants\User;

enum UserRole: int
{
    case ADMIN = 1;
    case MANAGER = 2;
    case EMPLOYEE = 3;
    case AGENCY = 4;
    case CUSTOMER = 5;

    public function label()
    {
        return match ($this) {
            UserRole::ADMIN => __('enums.UserRole.ADMIN'),
            UserRole::MANAGER => __('enums.UserRole.MANAGER'),
            UserRole::EMPLOYEE => __('enums.UserRole.EMPLOYEE'),
            UserRole::AGENCY => __('enums.UserRole.AGENCY'),
            UserRole::CUSTOMER => __('enums.UserRole.CUSTOMER'),
        };
    }

}
