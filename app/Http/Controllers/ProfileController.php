<?php

namespace App\Http\Controllers;

use App\Common\Constants\CommonConstant;
use App\Common\Constants\Otp\Otp;
use App\Core\Controller;
use App\Core\FlashMessage;
use App\Core\Logging;
use App\Http\Requests\Profile\ChangePasswordRequest;
use App\Http\Requests\Profile\ConnectTelegramRequest;
use App\Http\Requests\Profile\ProfileUpdateRequest;
use App\Http\Requests\Profile\VerifyEmailOtpRequest;
use App\Models\User;
use App\Service\AuthService;
use App\Service\MailService;
use App\Service\OtpService;
use App\Service\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected AuthService $authService,
        protected MailService $mailService,
        protected OtpService $otpService,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        return $this->rendering(
            view: 'profile/index',
            data: [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'telegram_id' => $user->telegram_id,
                    'whatsapp_id' => $user->whatsapp_id,
                    'email_verified_at' => $user->email_verified_at,
                ],
                    'telegram' => [
                        'bot_id' => config('services.telegram.bot_id'),
                        'callback_url' => route('profile', absolute: true),
                    ],
            ],
        );
    }

    public function connectTelegram(ConnectTelegramRequest $request): RedirectResponse
    {
        $user = $request->user();

        $result = $this->authService->verifyHashTelegram($request->validated());
        if ($result->isError()) {
            FlashMessage::error($result->getMessage());
            return back()->withInput();
        }

        $user->telegram_id = (string) $request->validated('id');
        $user->save();

        FlashMessage::success(__('profile.telegram_connect_success'));
        return redirect()->route('profile');
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $result = $this->userService->updateProfile(
            user: $request->user(),
            data: $request->validated(),
        );

        if ($result->isSuccess()) {
            FlashMessage::success(message: __('common_success.update_success'));
            $payload = $result->getData();
            if (!empty($payload['email_changed']) && ($payload['user'] ?? null) instanceof User) {
                $this->sendVerificationEmail($payload['user']);
            }
        } else {
            FlashMessage::error($result->getMessage());
        }

        return redirect()->route('profile');
    }

    public function resendEmail(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (!$user->email) {
            FlashMessage::error(__('profile.email_missing'));
            return back();
        }
        if ($user->email_verified_at) {
            FlashMessage::info(__('profile.email_already_verified'));
            return back();
        }

        $this->sendVerificationEmail($user);
        return back();
    }

    public function verifyEmailOtp(VerifyEmailOtpRequest $request): RedirectResponse
    {
        $user = $request->user();
        if (!$user->email) {
            FlashMessage::error(__('profile.email_missing'));
            return back();
        }
        if ($user->email_verified_at) {
            FlashMessage::info(__('profile.email_already_verified'));
            return back();
        }

        $otp = $request->validated('otp');

        // Xác minh mã
        $verifyResult = $this->otpService->verifyOtp(
            userId: (string) $user->id,
            code: $otp,
            type: Otp::EMAIL_VERIFICATION
        );

        if ($verifyResult->isError()) {
            FlashMessage::error(__('profile.otp_invalid'));
            return back()->withInput();
        }

        // Xác minh email thành công
        $user->email_verified_at = now();
        $user->save();

        FlashMessage::success(__('profile.email_verified_success'));
        return redirect()->route('profile');
    }

    public function changePassword(ChangePasswordRequest $request): RedirectResponse
    {
        Logging::web('ProfileController@changePassword: Request received');

        $result = $this->userService->changePassword(
            user: $request->user(),
            currentPassword: $request->validated('current_password'),
            newPassword: $request->validated('new_password')
        );

        if ($result->isSuccess()) {
            Logging::web('ProfileController@changePassword: Password changed successfully');
            FlashMessage::success(__('profile.password_changed_success'));
        } else {
            Logging::web('ProfileController@changePassword: Failed to change password', [
                'error' => $result->getMessage(),
            ]);
            FlashMessage::error($result->getMessage());
        }

        return redirect()->route('profile');
    }

    protected function sendVerificationEmail(User $user): void
    {
        if (empty($user->email)) {
            return;
        }

        $expireMin = CommonConstant::OTP_EXPIRE_MIN;
        
        // Generate OTP using OtpService
        $otpResult = $this->otpService->generateOtp(
            userId: (string) $user->id,
            type: Otp::EMAIL_VERIFICATION,
            expireMinutes: $expireMin
        );

        if ($otpResult->isError()) {
            FlashMessage::warning(__('profile.verification_email_failed'));
            return;
        }

        $otpData = $otpResult->getData();
        $otp = $otpData['code'];

        $mailResult = $this->mailService->sendVerifyRegister(
            email: $user->email,
            username: $user->username ?? $user->name,
            otp: $otp,
            expireMin: $expireMin,
        );

        if ($mailResult->isSuccess()) {
            FlashMessage::info(__('profile.verification_email_sent', ['email' => $user->email]));
        } else {
            FlashMessage::warning(__('profile.verification_email_failed'));
        }
    }
}

