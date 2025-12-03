<?php

namespace App\Service;

use App\Core\Logging;
use App\Core\ServiceReturn;
use App\Mail\VerifyEmailForgotPassword;
use App\Mail\VerifyEmailRegister;
use App\Mail\WalletLowBalanceAlert;
use App\Mail\WalletTransactionAlert;
use App\Mail\AdminWalletTransactionAlert;
use App\Mail\ServiceUserStatusAlert;
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

    public function sendWalletLowBalanceAlert(string $email, string $username, float $balance, float $threshold): ServiceReturn
    {
        try {
            Mail::to($email)->queue(new WalletLowBalanceAlert(
                username: $username,
                balance: number_format($balance, 2),
                threshold: number_format($threshold, 2),
            ));

            return ServiceReturn::success();
        } catch (\Throwable $exception) {
            Logging::error('MailService@sendWalletLowBalanceAlert: Failed to queue email', [
                'email' => $email,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            return ServiceReturn::error(message: 'Failed to send wallet low balance email: ' . $exception->getMessage());
        }
    }

    public function sendWalletTransactionAlert(string $email, string $username, string $typeLabel, float $amount, ?string $description = null): ServiceReturn
    {
        try {
            Mail::to($email)->queue(new WalletTransactionAlert(
                username: $username,
                typeLabel: $typeLabel,
                amount: number_format($amount, 2),
                description: $description,
            ));

            return ServiceReturn::success();
        } catch (\Throwable $exception) {
            Logging::error('MailService@sendWalletTransactionAlert: Failed to queue email', [
                'email' => $email,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            return ServiceReturn::error(message: 'Failed to send wallet transaction email: ' . $exception->getMessage());
        }
    }

    public function sendAdminWalletTransactionAlert(string $email, string $adminName, string $customerName, string $typeLabel, float $amount, ?string $stage = null, ?string $description = null): ServiceReturn
    {
        try {
            Mail::to($email)->queue(new AdminWalletTransactionAlert(
                adminName: $adminName,
                customerName: $customerName,
                transactionType: $typeLabel,
                amount: number_format($amount, 2),
                stage: $stage,
                description: $description,
            ));

            return ServiceReturn::success();
        } catch (\Throwable $exception) {
            Logging::error('MailService@sendAdminWalletTransactionAlert: Failed to queue email', [
                'email' => $email,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            return ServiceReturn::error(message: 'Failed to send admin wallet transaction email: ' . $exception->getMessage());
        }
    }

    public function sendServiceUserStatusAlert(string $email, string $username, string $packageName, string $statusKey): ServiceReturn
    {
        try {
            Mail::to($email)->queue(new ServiceUserStatusAlert(
                username: $username,
                packageName: $packageName,
                statusKey: $statusKey,
            ));

            return ServiceReturn::success();
        } catch (\Throwable $exception) {
            Logging::error('MailService@sendServiceUserStatusAlert: Failed to queue email', [
                'email' => $email,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            return ServiceReturn::error(message: 'Failed to send service user status email: ' . $exception->getMessage());
        }
    }
}
