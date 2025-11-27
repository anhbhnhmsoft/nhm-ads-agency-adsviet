<?php

namespace App\Service;

use App\Core\Logging;
use App\Core\ServiceReturn;
use App\Mail\VerifyEmailForgotPassword;
use App\Mail\VerifyEmailRegister;
use Illuminate\Support\Facades\Mail;

class MailService
{
    public function sendVerifyRegister(string $email, string $username, string $otp, int $expireMin): ServiceReturn
    {
        try {
            Mail::to($email)->queue(new VerifyEmailRegister(
                otp: $otp,
                username: $username,
                expireTime: $expireMin
            ));

            return ServiceReturn::success();
        } catch (\Throwable $exception) {
            Logging::error('MailService@sendVerifyRegister: Failed to queue email', [
                'email' => $email,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            return ServiceReturn::error(message: 'Failed to send verification email: ' . $exception->getMessage());
        }
    }

    public function sendVerifyForgotPassword(string $email, string $username, string $otp, int $expireTime): ServiceReturn
    {
        try {
            Mail::to($email)->queue(new VerifyEmailForgotPassword(
                otp: $otp,
                username: $username,
                expireTime: $expireTime
            ));

            return ServiceReturn::success();
        } catch (\Throwable $exception) {
            Logging::error('MailService@sendVerifyForgotPassword: Failed to queue email', [
                'email' => $email,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            return ServiceReturn::error(message: 'Failed to send forgot password email: ' . $exception->getMessage());
        }
    }

}
