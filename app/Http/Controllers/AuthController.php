<?php

namespace App\Http\Controllers;

use App\Common\Constants\Otp\Otp;
use App\Core\Controller;
use App\Core\FlashMessage;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Requests\SendWhatsappOtpRequest;
use App\Http\Requests\VerifyWhatsappOtpRequest;
use App\Service\AuthService;
use App\Service\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{


    public function __construct(
        protected AuthService $authService,
        protected OtpService $otpService
    )
    {
    }

    public function loginScreen(): \Inertia\Response
    {
        return $this->rendering('auth/login', [
            'bot_username' => config('services.telegram.bot_username'),
        ]);
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
            // set cache để đăng ký tài khoản mới
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
        }

        $result = $this->authService->handleRegisterNewUser($form);
        if ($result->isSuccess()) {
            FlashMessage::success(__('common_success.register_success'));
            return redirect()->route('dashboard');
        } else {
            FlashMessage::error($result->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function sendWhatsappOtp(SendWhatsappOtpRequest $request)
    {
        $phone = $request->input('phone');
        // Lưu phone tạm thời vào session để gắn với OTP (vì bảng user_otp không có cột phone)
        Session::put('register_whatsapp_phone', $phone);

        $result = $this->otpService->generateOtp(Otp::VERIFY);
        if ($result->isError()) {
            return response()->json(['success' => false, 'message' => $result->getMessage()], 422);
        }

        $code = $result->getData()['code'] ?? null;
        // TODO: tích hợp gửi template WhatsApp tại đây. Hiện tạm thời log để kiểm thử
        Log::info('Send WhatsApp OTP', ['phone' => $phone, 'code' => $code]);

        return response()->json(['success' => true]);
    }

    public function verifyWhatsappOtp(VerifyWhatsappOtpRequest $request)
    {
        $otp = $request->input('otp');
        $result = $this->otpService->verifyOtp($otp, Otp::VERIFY);
        if ($result->isError()) {
            return response()->json(['success' => false, 'message' => $result->getMessage()], 422);
        }

        $phone = Session::get('register_whatsapp_phone');
        if (!$phone) {
            return response()->json(['success' => false, 'message' => __('auth.register.validation.otp_invalid')], 422);
        }

        // Đánh dấu đã xác thực WhatsApp để sang bước register-new-user
        Session::put('register_social', [
            'type' => 'whatsapp',
            'data' => ['phone' => $phone],
        ]);

        return response()->json(['success' => true]);
    }
}
