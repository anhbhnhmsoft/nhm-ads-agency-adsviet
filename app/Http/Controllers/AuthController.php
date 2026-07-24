<?php

namespace App\Http\Controllers;

use App\Common\Constants\Otp\Otp;
use App\Core\Controller;
use App\Core\FlashMessage;
use App\Http\Requests\Auth\RegisterEmailOtpRequest;
use App\Http\Requests\Auth\VerifyRegisterEmailOtpRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use App\Service\AuthService;
use App\Service\MailService;
use App\Service\OtpService;
use App\Service\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;

class AuthController extends Controller
{


    public function __construct(
        protected AuthService $authService,
        protected OtpService $otpService,
        protected WalletService $walletService,
        protected MailService $mailService,
    )
    {
    }

    public function loginScreen(): \Inertia\Response
    {
        return $this->rendering('auth/login', [
            'bot_username' => config('services.telegram.bot_username'),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->authService->handleLogout();
        return redirect()->route('login');
    }

    /**
     * Handle login with username
     * @param LoginRequest $request
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function handleLoginUsername(LoginRequest $request): RedirectResponse
    {
        $result = $this->authService->handleLoginUsername($request->validated());

        if ($result->isSuccess()) {
            FlashMessage::success(__('common_success.login_success'));
            return redirect()->route('dashboard');
        } else {
            throw ValidationException::withMessages([
                'username' => $result->getMessage(),
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    public function handleLoginTelegram(Request $request): RedirectResponse
    {
        $telegramData = $request->only(['id', 'first_name', 'last_name', 'username', 'photo_url', 'auth_date', 'hash']);

        // Kiểm tra hash telegram
        $validateHash = $this->authService->verifyHashTelegram($telegramData);
        if ($validateHash->isError()) {
            FlashMessage::error($validateHash->getMessage());
            throw ValidationException::withMessages([
                'error' => $validateHash->getMessage(),
            ]);
        }

        // Kiểm tra telegram id có tồn tại trong hệ thống hay không
        $result = $this->authService->handleQuickLoginTelegram($telegramData['id']);
        if ($result->isError()) {
            FlashMessage::error($result->getMessage());
            throw ValidationException::withMessages([
                'error' => $result->getMessage(),
            ]);
        }
        // Kiểm tra người dùng có cần đăng ký hay không
        $status = $result->getData()['need_register'];
        if ($status) {
            // set session để đăng ký tài khoản mới
            Session::put('register_social',[
                'type' => 'telegram',
                'data' => $telegramData,
            ]);
            return redirect()->route('auth_register_new_user_screen');
        } else {
            FlashMessage::success(__('common_success.login_success'));
            return redirect()->route('dashboard');
        }
    }

    public function sendRegisterEmailOtp(RegisterEmailOtpRequest $request): RedirectResponse
    {
        $email = $request->validated('email');

        Session::put('register_social', [
            'type' => 'gmail',
            'data' => [
                'email' => $email,
            ],
        ]);

        return redirect()->route('auth_register_new_user_screen');
    }

    public function verifyRegisterEmailOtp(VerifyRegisterEmailOtpRequest $request): RedirectResponse
    {
        $payload = session()->get('register_email_otp');
        $email = $request->validated('email');
        $otp = $request->validated('otp');

        if (!$payload || ($payload['email'] ?? null) !== $email) {
            FlashMessage::error(__('auth.register.email_otp_mismatch'));
            return back()->withInput();
        }

        if (!isset($payload['expired_at']) || now()->greaterThan($payload['expired_at'])) {
            FlashMessage::error(__('auth.register.email_otp_expired'));
            return back()->withInput();
        }

        if (($payload['otp'] ?? null) !== $otp) {
            FlashMessage::error(__('auth.register.email_otp_invalid'));
            return back()->withInput();
        }

        Session::put('register_social', [
            'type' => 'gmail',
            'data' => [
                'email' => $email,
            ],
        ]);

        session()->forget('register_email_otp');

        return redirect()->route('auth_register_new_user_screen');
    }

    /**
     * Handle register new user screen
     * @return \Inertia\Response|RedirectResponse
     */
    public function registerNewUserScreen(): \Inertia\Response|RedirectResponse
    {
        // Kiểm tra có dữ liệu social login trước đó hay không
        if (!Session::has('register_social')) {
            FlashMessage::error(__('auth.login.validation.choose_social_first'));
            return redirect()->route('register');
        }

        return $this->rendering('auth/register-new-user',[
            'social_data' => Session::get('register_social'),
        ]);
    }

    public function registerScreen(): \Inertia\Response
    {
        return $this->rendering('auth/register', [
            'bot_username' => config('services.telegram.bot_username'),
        ]);
    }

    public function handleRegisterNewUser(RegisterUserRequest $request): RedirectResponse
    {
        $form = $request->validated();
        // Kiểm tra có dữ liệu social login trước đó hay không
        if (!Session::has('register_social')) {
            FlashMessage::error(__('auth.register.validation.choose_social_first'));
            return redirect()->route('login');
        }
        $registerSocial = Session::get('register_social');
        $registerSocialData = $registerSocial['data'];
        if ($registerSocial['type'] == 'telegram') {
            // validate telegram data
            $validateTelegram = $this->authService->verifyHashTelegram($registerSocialData);
            if ($validateTelegram->isError()) {
                FlashMessage::error($validateTelegram->getMessage());
                return redirect()->route('login');
            }
            // merger telegram data with register data
            $form['type'] = 'telegram';
            $form['telegram_id'] = $registerSocialData['id'];
        } elseif ($registerSocial['type'] === 'gmail') {
            $form['type'] = 'gmail';
            $form['email'] = $registerSocialData['email'] ?? null;
        }

        $result = $this->authService->handleRegisterNewUser($form);
        if ($result->isSuccess()) {
            Session::forget('register_social');
            $userId = Auth::id();
            if ($userId) {
                $walletResult = $this->walletService->createForUser($userId);
                if ($walletResult->isError()) {
                    FlashMessage::warning(__('auth.register.warning.wallet_creation_failed'));
                }
            }
            FlashMessage::success(__('common_success.register_success'));
            return redirect()->route('dashboard');
        } else {
            FlashMessage::error($result->getMessage());
            return redirect()->back()->withInput();
        }
    }

    // ── Forgot Password ──────────────────────────────────────────

    public function forgotPasswordScreen(): \Inertia\Response
    {
        return $this->rendering('auth/forgot-password');
    }

    public function sendForgotPasswordOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $request->input('email');
        $user = User::where('email', $email)->first();

        if ($user) {
            $otpResult = $this->otpService->generateOtp(
                (string) $user->id,
                Otp::FORGOT_PASSWORD,
                5,
            );

            if ($otpResult->isSuccess()) {
                $this->mailService->sendVerifyForgotPassword(
                    $email,
                    $user->username,
                    $otpResult->getData()['code'],
                    5,
                );
            }
        }

        // Luôn redirect để không lộ email có tồn tại hay không
        Session::put('forgot_password_email', $email);
        FlashMessage::success(__('auth.forgot_password.otp_sent', ['default' => 'Mã OTP đã được gửi đến email của bạn.']));
        return redirect()->route('auth_forgot_password_verify_screen');
    }

    public function forgotPasswordOtpScreen(): \Inertia\Response
    {
        if (!Session::has('forgot_password_email')) {
            return redirect()->route('auth_forgot_password_screen');
        }
        return $this->rendering('auth/forgot-password-otp', [
            'email' => Session::get('forgot_password_email'),
        ]);
    }

    public function verifyForgotPasswordOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'digits:6'],
        ]);

        $email = Session::get('forgot_password_email');
        if (!$email) {
            return redirect()->route('auth_forgot_password_screen');
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            FlashMessage::error(__('auth.forgot_password.email_not_found', ['default' => 'Email không tồn tại trong hệ thống.']));
            return redirect()->route('auth_forgot_password_screen');
        }

        $result = $this->otpService->verifyOtp(
            (string) $user->id,
            $request->input('otp'),
            Otp::FORGOT_PASSWORD,
        );

        if ($result->isError()) {
            return back()->withErrors(['otp' => $result->getMessage()]);
        }

        Session::put('forgot_password_verified', true);
        return redirect()->route('auth_forgot_password_reset_screen');
    }

    public function forgotPasswordResetScreen(): \Inertia\Response
    {
        if (!Session::get('forgot_password_verified')) {
            return redirect()->route('auth_forgot_password_screen');
        }
        return $this->rendering('auth/forgot-password-reset');
    }

    public function resetForgotPassword(Request $request): RedirectResponse
    {
        if (!Session::get('forgot_password_verified')) {
            return redirect()->route('auth_forgot_password_screen');
        }

        $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $email = Session::get('forgot_password_email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('auth_forgot_password_screen');
        }

        $user->update(['password' => Hash::make($request->input('password'))]);

        Session::forget(['forgot_password_email', 'forgot_password_verified']);

        FlashMessage::success(__('auth.forgot_password.reset_success', ['default' => 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập.']));
        return redirect()->route('login');
    }
}
