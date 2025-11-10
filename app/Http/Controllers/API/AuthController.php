<?php

namespace App\Http\Controllers\API;

use App\Common\Constants\User\UserRole;
use App\Core\Controller;
use App\Core\RestResponse;
use App\Http\Resources\AuthResource;
use App\Models\User;
use App\Rules\PasswordRule;
use App\Service\AuthService;
use App\Service\TelegramService;
use App\Service\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Service\MailService;
class AuthController extends Controller
{

    public function __construct(
        protected AuthService $authService,
        protected TelegramService $telegramService,
        protected WalletService $walletService,
        protected MailService $mailService,
    )
    {
    }

    /**
     * Xử lý đăng nhập
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string', 'max:255'],
            'password' => [new PasswordRule],
            'remember' => 'boolean',
        ], [
            'username.required' => __('common_validation.username.required'),
            'username.string' => __('common_validation.username.string'),
            'username.max' => __('common_validation.username.max', ['max' => 255]),
        ]);

        if ($validator->fails()) {
            return RestResponse::validation(
                errors: $validator->errors()->toArray()
            );
        }
        $data = $validator->getData();
        $result = $this->authService->handleLogin($data, true);
        if ($result->isError()) {
            return RestResponse::error(
                message: $result->getMessage(),
            );
        }
        $serviceData = $result->getData();
        return RestResponse::success(
            data: [
                'token' => $serviceData['token'],
                'user' => new AuthResource($serviceData['user']),
            ],
            message: __('auth.login.success')
        );
    }

    /**
     * Xử lý đăng ký user (bằng email)
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'name' => 'required|string|max:255',
                'username' => ['required', 'string', 'max:255', 'unique:users,username'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => [new PasswordRule],
                'role' => ['required', Rule::in([UserRole::CUSTOMER->value, UserRole::AGENCY->value])],
                'refer_code' => 'required|string|exists:users,referral_code',
            ],
            [
                'name.required' => __('common_validation.name.required'),
                'name.string' => __('common_validation.name.string'),
                'name.max' => __('common_validation.name.max', ['max' => 255]),
                'username.required' => __('common_validation.username.required'),
                'username.string' => __('common_validation.username.string'),
                'username.max' => __('common_validation.username.max', ['max' => 255]),
                'username.unique' => __('common_validation.username.unique'),
                'email.required' => __('common_validation.email.required'),
                'email.string' => __('common_validation.email.string'),
                'email.email' => __('common_validation.email.email'),
                'email.max' => __('common_validation.email.max', ['max' => 255]),
                'role.required' => __('common_validation.role.required'),
                'role.in' => __('common_validation.role.invalid'),
                'refer_code.required' => __('common_validation.refer_code.required'),
                'refer_code.string' => __('common_validation.refer_code.invalid'),
                'refer_code.exists' => __('common_validation.refer_code.invalid'),
            ]
        );
        if ($validator->fails()) {
            return RestResponse::validation(
                errors: $validator->errors()->toArray()
            );
        }
        // Đăng ký user
        $resultRegisterUser = $this->authService->handleRegister($validator->getData());
        if ($resultRegisterUser->isError()) {
            return RestResponse::error(
                message: $resultRegisterUser->getMessage(),
            );
        }
        $serviceData = $resultRegisterUser->getData();

        // Gửi mail
        $this->mailService->sendVerifyRegister(
            email: $serviceData['user']->email,
            username: $serviceData['user']->username,
            otp: $serviceData['otp'],
            expireMin: $serviceData['expire_time'],
        );

        // Tạo ví cho user
        $this->walletService->createForUser($serviceData['user']->id);

        return RestResponse::success(
            data: [
                'user_id' => $serviceData['user']->id,
                'user_email' => $serviceData['user']->email,
                'expire_time' => $serviceData['expire_time'],
            ],
            message: __('auth.register.success')
        );
    }

    /**
     * Xử lý xác thực đăng ký user (bằng email)
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyRegister(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'user_id' => ['required', 'string', 'exists:users,id'],
                'code' => ['required', 'string', 'max:6'],
            ],
            [
                'user_id.required' => __('common_validation.user_id.required'),
                'user_id.string' => __('common_validation.user_id.string'),
                'user_id.exists' => __('common_validation.user_id.exists'),
                'code.required' => __('common_validation.otp_invalid'),
                'code.string' => __('common_validation.otp_invalid'),
                'code.max' => __('common_validation.otp_invalid'),
            ]
        );
        if ($validator->fails()) {
            return RestResponse::validation(
                errors: $validator->errors()->toArray()
            );
        }
        $result = $this->authService->handleVerifyRegister($validator->getData());
        if ($result->isError()) {
            return RestResponse::error(
                message: $result->getMessage(),
            );
        }
        return RestResponse::success(message: __('auth.verify_register.success'));
    }

    /**
     * Xử lý login từ Telegram
     * @param Request $request
     * @return JsonResponse
     */
    public function telegramLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Telegram data
            'id' => ['required', 'integer'],
            'first_name' => ['nullable'],
            'last_name' => ['nullable'],
            'photo_url' => ['nullable'],
            'auth_date' => ['required', 'integer'],
            'hash' => ['required', 'string'],
        ], [
            'id.required' => __('auth.login.validation.telegram_hash_invalid'),
            'id.integer' => __('auth.login.validation.telegram_hash_invalid'),
            'auth_date.required' => __('auth.login.validation.telegram_hash_invalid'),
            'auth_date.integer' => __('auth.login.validation.telegram_hash_invalid'),
            'auth_date.max' => __('auth.login.validation.telegram_hash_invalid'),
            'hash.required' => __('auth.login.validation.telegram_hash_invalid'),
            'hash.string' => __('auth.login.validation.telegram_hash_invalid'),
        ]);
        if ($validator->fails()) {
            return RestResponse::validation(
                errors: $validator->errors()->toArray()
            );
        }
        // Xử lý login từ Telegram
        $result = $this->authService->handleAuthTelegram($validator->getData());
        if ($result->isError()) {
            return RestResponse::error(
                message: $result->getMessage(),
            );
        }
        $serviceData = $result->getData();

        // Kiểm tra người dùng có cần đăng ký hay không
        if ($serviceData['need_register']) {
            return RestResponse::success(
                data: [
                    'need_register' => true,
                    // token ở đây dùng để xác thực đăng ký sau, không phải là token đăng nhập
                    'token' => $serviceData['token'],
                ],
                message: __('auth.login.need_register')
            );
        } else {
            return RestResponse::success(
                data: [
                    'need_register' => false,
                    'auth' => [
                        'token' => $serviceData['token'],
                        'user' => new AuthResource($serviceData['user']),
                    ]
                ],
                message: __('auth.login.success')
            );
        }
    }

    /**
     * Xử lý callback từ Telegram để lấy token rồi trả về app
     */
    public function telegramCallback(): \Illuminate\Http\RedirectResponse
    {
        // 302 Redirect to mobile deep link with token
        return redirect()->away(config('services.mobile_deep_link'));
    }

    /**
     * Đăng ký tài khoản mới (thông qua Telegram)
     * @param Request $request
     * @return JsonResponse
     */
    public function registerTelegram(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'name' => 'required|string|max:255',
                'username' => ['required', 'string', 'max:255', 'unique:users,username'],
                'password' => [new PasswordRule],
                'role' => ['required', Rule::in([UserRole::CUSTOMER->value, UserRole::AGENCY->value])],
                'refer_code' => 'required|string|exists:users,referral_code',
                'token' => ['required', 'string'],
            ],
            [
                'name.required' => __('common_validation.name.required'),
                'name.string' => __('common_validation.name.string'),
                'name.max' => __('common_validation.name.max', ['max' => 255]),
                'username.required' => __('common_validation.username.required'),
                'username.string' => __('common_validation.username.string'),
                'username.max' => __('common_validation.username.max', ['max' => 255]),
                'username.unique' => __('common_validation.username.unique'),
                'role.required' => __('common_validation.role.required'),
                'role.in' => __('common_validation.role.invalid'),
                'refer_code.required' => __('common_validation.refer_code.required'),
                'refer_code.string' => __('common_validation.refer_code.invalid'),
                'refer_code.exists' => __('common_validation.refer_code.invalid'),
                'token.required' => __('common_validation.token_invalid'),
                'token.string' => __('common_validation.token_invalid'),
            ]
        );
        if ($validator->fails()) {
            return RestResponse::validation(
                errors: $validator->errors()->toArray()
            );
        }

        $form = $validator->getData();

        $result = $this->authService->handleRegisterTelegram($form, true);
        if ($result->isError()) {
            return RestResponse::error(
                message: $result->getMessage(),
                status: 403
            );
        }
        $serviceData = $result->getData();

        // Tạo ví cho user
        $this->walletService->createForUser($serviceData['user']->id);

        return RestResponse::success(
            data: [
                'token' => $serviceData['token'],
                'user' => new AuthResource($serviceData['user']),
            ],
            message: __('auth.login.success')
        );
    }

    /**
     * Lấy thông tin profile của user
     * @return JsonResponse
     */
    public function getProfile(): JsonResponse
    {
        $result = $this->authService->handleGetProfile();
        if ($result->isError()) {
            return RestResponse::error(
                message: $result->getMessage(),
                status: 403
            );
        }
        $serviceData = $result->getData();
        return RestResponse::success(
            data: [
                'user' => new AuthResource($serviceData['user']),
            ],
        );
    }

    /**
     * Quên mật khẩu, gửi OTP
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string', 'max:255'],
        ], [
            'username.required' => __('common_validation.username.required'),
            'username.string' => __('common_validation.username.string'),
            'username.max' => __('common_validation.username.max', ['max' => 255]),
        ]);
        if ($validator->fails()) {
            return RestResponse::validation(
                errors: $validator->errors()->toArray()
            );
        }
        $form = $validator->getData();
        $resultService = $this->authService->handleForgotPassword($form['username']);
        if ($resultService->isError()) {
            return RestResponse::error(
                message: $resultService->getMessage(),
                status: 403
            );
        }
        /**
         * @var User $user
         */
        $dataService = $resultService->getData();
        if ($dataService['send_to'] === 'telegram') {
            $resultOtp = $this->telegramService->handleSendOTP(
                telegramId: $dataService['user']->telegram_id,
                otp: $dataService['otp'],
                expireTime: $dataService['expire_time'],
            );
            if ($resultOtp->isError()) {
                return RestResponse::error(
                    message: $resultOtp->getMessage(),
                    status: 403
                );
            }
        }else{
            $this->mailService->sendVerifyForgotPassword(
                email: $dataService['user']->email,
                username: $dataService['user']->username,
                otp: $dataService['otp'],
                expireTime: $dataService['expire_time'],
            );
        }
        return RestResponse::success(
            data: [
                'username' => $dataService['user']->username,
                'expire_time' => $dataService['expire_time'],
            ],
            message: __('auth.forgot_password.success'),
        );
    }

    /**
     * Xác thực OTP để thay đổi mật khẩu
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyForgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'min:6', 'max:6'],
            'password' => [new PasswordRule()],
        ], [
            'username.required' => __('common_validation.username.required'),
            'username.string' => __('common_validation.username.string'),
            'username.max' => __('common_validation.username.max', ['max' => 255]),
            'code.required' => __('auth.verify_forgot_password.validation.otp_invalid'),
            'code.string' => __('auth.verify_forgot_password.validation.otp_invalid'),
            'code.min' => __('auth.verify_forgot_password.validation.otp_invalid'),
            'code.max' => __('auth.verify_forgot_password.validation.otp_invalid'),
        ]);
        if ($validator->fails()) {
            return RestResponse::validation(
                errors: $validator->errors()->toArray()
            );
        }
        $form = $validator->getData();
        $result = $this->authService->handleVerifyForgotPassword($form);
        if ($result->isError()) {
            return RestResponse::error(
                message: $result->getMessage(),
                status: 403
            );
        }
        return RestResponse::success(
            message: __('auth.verify_forgot_password.success'),
        );
    }
}
