<?php

namespace App\Http\Controllers\API;

use App\Common\Constants\User\UserRole;
use App\Core\Controller;
use App\Core\RestResponse;
use App\Http\Resources\AuthResource;
use App\Rules\PasswordRule;
use App\Service\AuthService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{

    public function __construct(protected AuthService $authService)
    {
    }

    public function LoginUsername(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string', 'max:255'],
            'password' => [new PasswordRule],
            'platform' => ['required', 'in:ios,android'],
            'device_id' => ['required', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ], [
            'email.required' => __('auth.validation.email_required'),
            'email.email' => __('auth.validation.email_email'),
            'password.required' => __('auth.validation.password_required'),
            'password.min' => __('auth.validation.password_min'),
            'platform.required' => __('auth.login.validation.device_required'),
            'platform.in' => __('auth.login.validation.device_in'),
            'device_id.required' => __('auth.login.validation.device_id_required'),
            'device_name.string' => __('auth.login.validation.device_name_string'),
            'device_name.max' => __('auth.login.validation.device_name_max'),
        ]);

        if ($validator->fails()) {
            return RestResponse::validation(
                errors: $validator->errors()->toArray()
            );
        }
        $data = $validator->getData();
        $result = $this->authService->handleLoginUsername($data, true);
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
     * Xử lý callback từ Telegram để lấy token rồi trả về app
     */
    public function handleTelegramCallback(): \Illuminate\Http\RedirectResponse
    {
        // 302 Redirect to mobile deep link with token
        return redirect()->away(config('services.mobile_deep_link'));
    }

    /**
     * Xử lý login từ Telegram
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function handleTelegramLogin(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Telegram data
            'id' => ['required', 'integer'],
            'first_name' => ['nullable'],
            'last_name' => ['nullable'],
            'photo_url' => ['nullable'],
            'auth_date' => ['required', 'integer'],
            'hash' => ['required', 'string'],
            // device info
            'platform' => ['required', 'in:ios,android'],
            'device_id' => ['required', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ], [
            'id.required' => __('auth.login.validation.telegram_hash_invalid'),
            'id.integer' => __('auth.login.validation.telegram_hash_invalid'),
            'auth_date.required' => __('auth.login.validation.telegram_hash_invalid'),
            'auth_date.integer' => __('auth.login.validation.telegram_hash_invalid'),
            'auth_date.max' => __('auth.login.validation.telegram_hash_invalid'),
            'hash.required' => __('auth.login.validation.telegram_hash_invalid'),
            'hash.string' => __('auth.login.validation.telegram_hash_invalid'),
            'platform.required' => __('auth.login.validation.device_required'),
            'platform.in' => __('auth.login.validation.device_in'),
            'device_id.required' => __('auth.login.validation.device_id_required'),
            'device_name.string' => __('auth.login.validation.device_name_string'),
            'device_name.max' => __('auth.login.validation.device_name_max'),
        ]);
        if ($validator->fails()) {
            return RestResponse::validation(
                errors: $validator->errors()->toArray()
            );
        }
        $telegramData = $validator->getData();

        // Xác thực hash từ Telegram
        $validateHash = $this->authService->verifyHashTelegram([
            'id' => $telegramData['id'],
            'first_name' => $telegramData['first_name'],
            'last_name' => $telegramData['last_name'],
            'photo_url' => $telegramData['photo_url'],
            'auth_date' => $telegramData['auth_date'],
            'hash' => $telegramData['hash'],
        ]);

        if ($validateHash->isError()) {
            return RestResponse::error(
                message: $validateHash->getMessage(),
                status: 403
            );
        }
        // Xử lý login từ Telegram
        $result = $this->authService->handleAuthTelegram($telegramData);
        if ($result->isError()) {
            return RestResponse::error(
                message: $result->getMessage(),
            );
        }
        // Kiểm tra người dùng có cần đăng ký hay không
        $data = $result->getData();
        if ($data['need_register']) {
            return RestResponse::success(
                data: ['need_register' => true],
                message: __('auth.login.need_register')
            );
        } else {
            return RestResponse::success(
                data: [
                    'need_register' => false,
                    'auth' => [
                        'token' => $data['token'],
                        'user' => new AuthResource($data['user']),
                    ]
                ],
                message: __('auth.login.success')
            );
        }
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(),
            [
                'name' => 'required|string|max:255',
                'username' => ['required', 'string', 'max:255', 'unique:users,username'],
                'password' => [new PasswordRule],
                'role' => ['required', Rule::in([UserRole::CUSTOMER->value, UserRole::AGENCY->value])],
                'refer_code' => 'required|string|exists:users,referral_code',
                // telegram data
                'id' => ['required', 'integer'],
                'first_name' => ['nullable'],
                'last_name' => ['nullable'],
                'photo_url' => ['nullable'],
                'auth_date' => ['required', 'integer'],
                'hash' => ['required', 'string'],
                // device info
                'platform' => ['required', 'in:ios,android'],
                'device_id' => ['required', 'string', 'max:255'],
                'device_name' => ['nullable', 'string', 'max:255'],
            ],
            [
                'name.required' => __('common_validation.name.required'),
                'name.string' => __('common_validation.name.string'),
                'name.max' => __('common_validation.name.max', ['max' => 255]),
                'username.required' => __('common_validation.username.required'),
                'username.string' => __('common_validation.username.string'),
                'username.max' => __('common_validation.username.max', ['max' => 255]),
                'role.required' => __('auth.register.validation.role.required'),
                'role.in' => __('auth.register.validation.role.in'),
                'refer_code.required' => __('auth.register.validation.refer_code_required'),
                'refer_code.string' => __('auth.register.validation.refer_code_string'),
                'refer_code.exists' => __('auth.register.validation.refer_code_invalid'),
                'id.required' => __('auth.login.validation.telegram_hash_invalid'),
                'id.integer' => __('auth.login.validation.telegram_hash_invalid'),
                'auth_date.required' => __('auth.login.validation.telegram_hash_invalid'),
                'auth_date.integer' => __('auth.login.validation.telegram_hash_invalid'),
                'auth_date.max' => __('auth.login.validation.telegram_hash_invalid'),
                'hash.required' => __('auth.login.validation.telegram_hash_invalid'),
                'hash.string' => __('auth.login.validation.telegram_hash_invalid'),
                'platform.required' => __('auth.login.validation.device_required'),
                'platform.in' => __('auth.login.validation.device_in'),
                'device_id.required' => __('auth.login.validation.device_id_required'),
                'device_name.string' => __('auth.login.validation.device_name_string'),
                'device_name.max' => __('auth.login.validation.device_name_max'),
            ]
        );
        if ($validator->fails()) {
            return RestResponse::validation(
                errors: $validator->errors()->toArray()
            );
        }
        $form = $validator->getData();

        // Xác thực hash từ Telegram
        $validateHash = $this->authService->verifyHashTelegram([
            'id' => $form['id'],
            'first_name' => $form['first_name'],
            'last_name' => $form['last_name'],
            'photo_url' => $form['photo_url'],
            'auth_date' => $form['auth_date'],
            'hash' => $form['hash'],
        ]);
        if ($validateHash->isError()) {
            return RestResponse::error(
                message: $validateHash->getMessage(),
                status: 403
            );
        }
        // Hiện tại chỉ hỗ trợ đăng ký qua Telegram
        $form['type'] = 'telegram';
        $form['telegram_id'] = $form['id'];
        $result = $this->authService->handleRegisterNewUser($form, true);
        if ($result->isError()) {
            return RestResponse::error(
                message: $result->getMessage(),
                status: 403
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
}
