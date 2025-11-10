<?php

namespace App\Service;

use App\Core\ServiceReturn;
use App\Mail\VerifyEmailForgotPassword;
use App\Mail\VerifyEmailRegister;
use Illuminate\Support\Facades\Mail;

class MailService
{
    public function sendVerifyRegister(string $email, string $username, string $otp, int $expireMin): ServiceReturn
    {
        Mail::to($email)->queue(new VerifyEmailRegister(
            otp: $otp,
            username: $username,
            expireTime: $expireMin
        ));
        return ServiceReturn::success();
    }

    public function sendVerifyForgotPassword(string $email, string $username, string $otp, int $expireTime): ServiceReturn
    {
        Mail::to($email)->queue(new VerifyEmailForgotPassword(
            otp: $otp,
            username: $username,
            expireTime: $expireTime
        ));
        return ServiceReturn::success();
    }

}
