<?php

namespace App\Service;

use App\Common\Constants\CommonConstant;
use App\Common\Constants\DeviceType;
use App\Common\Constants\User\UserRole;
use App\Common\Helper;
use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\Logging;
use App\Core\ServiceReturn;
use App\Mail\VerifyEmailRegister;
use App\Models\User;
use App\Repositories\UserDeviceRepository;
use App\Repositories\UserOtpRepository;
use App\Repositories\UserReferralRepository;
use App\Repositories\UserRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(
        protected UserRepository         $userRepository,
        protected UserOtpRepository      $userOtpRepository,
        protected UserDeviceRepository   $userDeviceRepository,
        protected UserReferralRepository $userReferralRepository,
    )
    {
    }

    /**
     * Handle login with username
     * @param array{
     *      username: string,
     *      password: string,
     *      role?: 'admin'|'customer',
     *      remember_me?: bool
     * } $data
     * @param bool $forApi
     * @return ServiceReturn
     */
    public function handleLogin(array $data, bool $forApi = false): ServiceReturn
    {
        try {
            $user = $this->userRepository->getUserByUsername($data['username']);
            // kiểm tra password và user có tồn tại không
            if (!$user || !Hash::check($data['password'], $user->password)) {
                return ServiceReturn::error(message: __('auth.login.validation.invalid_credentials'));
            }
            // Nếu customer không có telegram hoặc whatsapp id thì phải kiểm tra xem đã xác thực email chưa
            if (
                (!empty($user->telegram_id) || !empty($user->whatsapp_id))
                && empty($user->email_verified_at)
            ) {
                return ServiceReturn::error(message: __('auth.login.validation.email_not_verified'));
            }

            // Kiểm tra username tồn tại trong hệ thống phù hợp với role
            if ($forApi || $data['role'] !== 'admin') {
                // api chỉ dùng cho khách hàng
                // Kiểm tra username trong hệ thống khách hàng
                if (!in_array($user->role, [UserRole::CUSTOMER->value, UserRole::AGENCY->value])) {
                    return ServiceReturn::error(message: __('auth.login.validation.invalid_credentials'));
                }
            } else {
                // Kiểm tra username trong hệ thống admin
                if (!in_array($user->role, [UserRole::ADMIN->value, UserRole::EMPLOYEE->value, UserRole::MANAGER->value])) {
                    return ServiceReturn::error(message: __('auth.login.validation.invalid_credentials'));
                }
            }
            $rememberMe = isset($data['remember_me']) && $data['remember_me'] === true;
            // Khởi tạo xác thực
            if ($forApi) {
                // Tạo token Sanctum
                $token = $user->createToken(
                    name: 'api-token',
                    expiresAt: $rememberMe ? now()->addDays(30) : null
                )->plainTextToken;
                return ServiceReturn::success(data: [
                    'token' => $token,
                    'user' => $user,
                ]);
            } else {
                Auth::guard('web')->login($user, $data['remember_me']);
                // Làm mới session để ngăn chặn tấn công fixation session
                request()->session()->regenerate();
                return ServiceReturn::success(data: [
                    'user' => $user,
                ]);
            }


        }
        catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi AuthService@handleLogin: ' . $exception->getMessage(),
                exception: $exception
            );
            // Logout / Revoke token nếu lỗi
            if (isset($user)) {
                if ($forApi) {
                    $user->currentAccessToken()?->delete();
                } else {
                    Auth::guard('web')->logout();
                }
            }
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function handleLoginUsername(array $data): ServiceReturn
    {
        return $this->handleLogin([
            'username' => $data['username'],
            'password' => $data['password'],
            'role' => $data['role'],
            'remember_me' => $data['remember'] ?? false,
        ], forApi: false);
    }

    /**
     * Xử lý đăng ký user
     * @param array{
     *      name: string,
     *      username: string,
     *      email: string,
     *      password: string,
     *      role: UserRole::CUSTOMER|UserRole::AGENCY,
     *      refer_code: string,
     * } $data
     * @return ServiceReturn
     */
    public function handleRegister(array $data): ServiceReturn
    {
        DB::beginTransaction();
        try {
            // Kiểm tra refer code có tồn tại trong hệ thống hay không
            $userRefer = $this->userRepository->getUserToRegisterByReferCode($data['refer_code']);
            if (!$userRefer) {
                return ServiceReturn::error(message: __('common_validation.refer_code.invalid'));
            }
            /**
             * Tạo mới user
             * @var User $user
             */
            $register = [
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'disabled' => false,
                'referral_code' => Helper::generateReferCodeUser(UserRole::from($data['role'])),
            ];
            $user = $this->userRepository->create($register);
            // Tạo mới user referral
            $this->userReferralRepository->create([
                'referrer_id' => $user->id,
                'referred_id' => $userRefer->id,
            ]);
            /**
             * Gửi mail và xác thực OTP
             */
            // sinh otp
            $otp = rand(100000, 999999);
            // Thời gian hết hạn OTP (tính theo phút)
            $expireMin = CommonConstant::OTP_EXPIRE_MIN;
            Caching::setCache(
                key: CacheKey::CACHE_EMAIL_REGISTER,
                value: $otp,
                uniqueKey: $user->id,
                expire: $expireMin,
            );
            DB::commit();
            return ServiceReturn::success(data: [
                'expire_time' => $expireMin,
                'otp' => $otp,
                'user' => $user,
            ]);
        }
        catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi khi đăng ký AuthService@handleRegister: ' . $exception->getMessage(),
                exception: $exception
            );
            DB::rollBack();
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Xử lý xác thực đăng ký
     * @param array{
     *      user_id: string,
     *      code: string,
     * } $data
     * @return ServiceReturn
     */
    public function handleVerifyRegister(array $data): ServiceReturn
    {
        $otp = Caching::getCache(
            key: CacheKey::CACHE_EMAIL_REGISTER,
            uniqueKey: $data['user_id'],
        );
        if (!$otp || $otp != $data['code']) {
            return ServiceReturn::error(message: __('common_validation.otp_invalid'));
        }
        try {
            $user = $this->userRepository->query()->find($data['user_id']);
            if (!$user) {
                return ServiceReturn::error(message: __('common_validation.user_not_found'));
            }
            $user->update([
                'email_verified_at' => now(),
            ]);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi khi xác thực OTP AuthService@handleVerifyRegister: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }

        return ServiceReturn::success();
    }

    /**
     * Xử lý đăng xuất
     * @param bool $forApi
     * @return ServiceReturn
     */
    public function handleLogout(bool $forApi = false): ServiceReturn
    {
        Auth::logout();
        Caching::flushCacheSession();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return ServiceReturn::success();
    }

    /**
     * Xử lý xác thực đăng nhập qua Telegram
     * @param array{
     *      id: string,
     *      first_name: string,
     *      last_name: string,
     *      photo_url: string,
     *      auth_date: int,
     *      hash: string,
     * } $data data telegram response
     * @param bool $forApi
     * @return ServiceReturn
     */
    public function handleAuthTelegram(array $data, bool $forApi = false): ServiceReturn
    {
        try {
            // Xác thực hash telegram
            $validateHash = $this->verifyHashTelegramInternal($data);
            if (!$validateHash) {
                return ServiceReturn::error(message: __('auth.login.validation.telegram_hash_invalid'));
            }
            // Kiểm tra user có tồn tại trong hệ thống hay không
            $user = $this->userRepository->getUserByTelegramId($data['id']);
            if (!$user) {
                // User chưa có trong hệ thống, trả về thông báo cần đăng ký
                // Khởi tạo token random để xác thực đăng ký
                $token = Helper::generateTokenRandom();
                // Lưu cache token với telegram data để xác thực đăng ký sau
                Caching::setCache(
                    key: CacheKey::CACHE_TELEGRAM_REGISTER,
                    value: $data,
                    uniqueKey: $token
                );
                return ServiceReturn::success(data: [
                    'need_register' => true,
                    'token' => $token,
                ]);
            }
            // Kiểm tra user có bị khóa hay không
            if ($user->disabled) {
                return ServiceReturn::error(message: __('auth.login.validation.user_disabled'));
            }

            // Khởi tạo xác thực
            if ($forApi){
                // Đối với API, chỉ cho phép đăng nhập với role AGENCY hoặc CUSTOMER
                if (!in_array($user->role, [UserRole::AGENCY->value, UserRole::CUSTOMER->value])) {
                    return ServiceReturn::error(message: __('auth.login.validation.user_not_allowed'));
                }
                $token = $user->createToken('api-token')->plainTextToken;

                return ServiceReturn::success(data: [
                    'need_register' => false,
                    'token' => $token,
                    'user' => $user,
                ]);
            }else{
                request()->session()->regenerate();
                Auth::guard('web')->login($user, true);
                return ServiceReturn::success(data: [
                    'need_register' => false
                ]);
            }
        } catch (QueryException $exception) {
            Logging::error(
                message: 'Lỗi kiểm tra telegram id AuthService@handleAuthTelegram: ' . $exception->getMessage(),
                exception: $exception
            );
            // Logout / Revoke token nếu lỗi
            if (isset($user)) {
                if ($forApi) {
                    $user->currentAccessToken()?->delete();
                }
                else {
                    Auth::guard('web')->logout();
                }
            }
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Xử lý đăng ký mới khi user chưa có trong hệ thống
     * @param array $data
     * @param bool $forApi
     * @return ServiceReturn
     */
    public function handleRegisterTelegram(array $data, bool $forApi = false): ServiceReturn
    {
        DB::beginTransaction();
        try {
            $telegramData = Caching::getCache(
                key: CacheKey::CACHE_TELEGRAM_REGISTER,
                uniqueKey: $data['token'],
            );
            if (!$telegramData) {
                return ServiceReturn::error(message: __('auth.register.validation.token_invalid'));
            }

            // Kiểm tra refer code có tồn tại trong hệ thống hay không
            $userRefer = $this->userRepository->getUserToRegisterByReferCode($data['refer_code']);
            if (!$userRefer) {
                return ServiceReturn::error(message: __('auth.register.validation.refer_code_invalid'));
            }

            /**
             * Tạo mới user
             * @var User $user
             */
            $register = [
                'name' => $data['name'],
                'username' => $data['username'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'disabled' => false,
                'referral_code' => Helper::generateReferCodeUser(UserRole::from($data['role'])),
                'telegram_id' => $telegramData['id'],
            ];
            $user = $this->userRepository->create($register);

            // Tạo mới user referral
            $this->userReferralRepository->create([
                'referrer_id' => $user->id,
                'referred_id' => $userRefer->id,
            ]);
            if ($forApi) {
                // Tạo token Sanctum
                $token = $user->createToken('api-token')->plainTextToken;
                DB::commit();
                return ServiceReturn::success(data: [
                    'token' => $token,
                    'user' => $user,
                ]);
            }
            else {
                /** @var User $user */
                Auth::guard('web')->login($user, true);
                DB::commit();
                request()->session()->regenerate();
                return ServiceReturn::success();
            }
        } catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi khi đăng ký AuthService@handleRegisterTelegram: ' . $exception->getMessage(),
                exception: $exception
            );
            DB::rollBack();
            // Logout / Revoke token nếu lỗi
            if ($forApi && isset($user)) {
                $user->currentAccessToken()?->delete();
            } else {
                Auth::guard('web')->logout();
            }
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Lấy thông tin profile của user
     * @return ServiceReturn
     */
    public function handleGetProfile(): ServiceReturn
    {
        $user = auth()->user();
        return ServiceReturn::success(data: [
            'user' => $user,
        ]);
    }

    /**
     * Xử lý quên mật khẩu
     * @param string $username
     * @return ServiceReturn
     */
    public function handleForgotPassword(string $username): ServiceReturn
    {
        try {
            /**
             * customer system mới có thể quên mật khẩu
             */
            $user = $this->userRepository->filterQuery([
                'username' => $username,
                'roles' => [UserRole::AGENCY->value, UserRole::CUSTOMER->value],
                'is_active' => true,
            ])->first();
            if (!$user) {
                return ServiceReturn::error(message: __('auth.forgot_password.validation.user_exists'));
            }
            /**
             * Kiểm tra user phải có thông tin social hoặc email verify thì mới quên mật khẩu
             */
            if (!$user->telegram_id && !$user->email_verified_at) {
                return ServiceReturn::error(message: __('auth.forgot_password.validation.social_or_email_verify'));
            }

            // Kiểm tra xem có OTP trước đó trong cache ko
            $cacheOtp = Caching::getCache(
                key: CacheKey::CACHE_FORGOT_PASSWORD,
                uniqueKey: $user->id,
            );
            if ($cacheOtp) {
                // Xóa OTP cũ trong cache khi có OTP trước đó
                Caching::clearCache(key: CacheKey::CACHE_FORGOT_PASSWORD, uniqueKey: $user->id);
            }
            // Sinh OTP mới
            $otp = rand(100000, 999999);
            // Thời gian hết hạn OTP (tính theo phút)
            $expireMin = CommonConstant::OTP_EXPIRE_MIN;
            Caching::setCache(
                key: CacheKey::CACHE_FORGOT_PASSWORD,
                value: $otp,
                uniqueKey: $user->id,
                expire: $expireMin,
            );

            // Mặc định là gửi email, nếu có telegram id thì gửi telegram bot
            $sendTo = 'email';
            if ($user->telegram_id) {
                $sendTo = 'telegram';
            }

            return ServiceReturn::success(data: [
                'otp' => $otp,
                'expire_time' => $expireMin,
                'user' => $user,
                'send_to' => $sendTo,
            ]);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi khi tìm user agency hoặc customer có telegram id AuthService@findCustomerAgencyHasTelegram: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: $exception->getMessage());
        }
    }

    /**
     * Xác nhận reset password
     * @param array $data
     * @return ServiceReturn
     */
    public function handleVerifyForgotPassword(array $data): ServiceReturn
    {
        try {
            /**
             * customer system mới có thể quên mật khẩu
             */
            $user = $this->userRepository->filterQuery([
                'username' => $data['username'],
                'roles' => [UserRole::AGENCY->value, UserRole::CUSTOMER->value],
                'is_active' => true,
            ])->first();
            if (!$user) {
                return ServiceReturn::error(message: __('auth.forgot_password.validation.user_exists'));
            }
            /**
             * Kiểm tra user phải có thông tin social hoặc email verify thì mới quên mật khẩu
             */
            if (!$user->telegram_id && !$user->email_verified_at) {
                return ServiceReturn::error(message: __('auth.forgot_password.validation.social_or_email_verify'));
            }

            // Kiểm tra OTP
            $cacheOtp = Caching::getCache(
                key: CacheKey::CACHE_FORGOT_PASSWORD,
                uniqueKey: $user->id,
            );
            if (!$cacheOtp || $cacheOtp != $data['code']) {
                return ServiceReturn::error(message: __('auth.verify_forgot_password.validation.otp_invalid'));
            }
            // Cập nhật password
            $this->userRepository->query()
                ->where('id', $user->id)
                ->update(['password' => Hash::make($data['password'])]);
            Caching::clearCache(key: CacheKey::CACHE_FORGOT_PASSWORD, uniqueKey: $user->id);
            return ServiceReturn::success();
        } catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi khi xác nhận reset password AuthService@handleVerifyForgotPassword: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: $exception->getMessage());
        }
    }

    public function handleQuickLoginTelegram(string $telegramId): ServiceReturn
    {
        try {
            $user = $this->userRepository->getUserByTelegramId($telegramId);
            if (!$user) {
                return ServiceReturn::success(data: [
                    'need_register' => true,
                ]);
            }

            if ($user->disabled) {
                return ServiceReturn::error(message: __('auth.login.validation.user_disabled'));
            }

            Auth::guard('web')->login($user, true);
            request()->session()->regenerate();

            return ServiceReturn::success(data: [
                'need_register' => false,
            ]);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi AuthService@handleQuickLoginTelegram: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function verifyHashTelegram(array $authData): ServiceReturn
    {
        $isValid = $this->verifyHashTelegramInternal($authData);
        if (!$isValid) {
            return ServiceReturn::error(message: __('auth.login.validation.telegram_hash_invalid'));
        }
        return ServiceReturn::success();
    }

    public function handleRegisterNewUser(array $data): ServiceReturn
    {
        DB::beginTransaction();
        try {
            // Kiểm tra refer code có tồn tại trong hệ thống hay không
            $userRefer = $this->userRepository->getUserToRegisterByReferCode($data['refer_code']);
            if (!$userRefer) {
                return ServiceReturn::error(message: __('common_validation.refer_code.invalid'));
            }

            $register = [
                'name' => $data['name'],
                'username' => $data['username'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'disabled' => false,
                'referral_code' => Helper::generateReferCodeUser(UserRole::from($data['role'])),
            ];

            // Thêm telegram_id nếu có
            if (isset($data['telegram_id'])) {
                $register['telegram_id'] = $data['telegram_id'];
            }

            $user = $this->userRepository->create($register);

            // Tạo mới user referral
            $this->userReferralRepository->create([
                'referrer_id' => $user->id,
                'referred_id' => $userRefer->id,
            ]);

            DB::commit();

            // Login user sau khi đăng ký
            Auth::guard('web')->login($user, true);
            request()->session()->regenerate();

            return ServiceReturn::success(data: [
                'user' => $user,
            ]);
        } catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi khi đăng ký AuthService@handleRegisterNewUser: ' . $exception->getMessage(),
                exception: $exception
            );
            DB::rollBack();
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Kiểm tra quyền truy cập của user
     * @param array $roles
     * @return ServiceReturn
     */
    public function checkAccess(array $roles): ServiceReturn
    {
        $user = Auth::user();
        if (!$user) {
            return ServiceReturn::error(message: __('common_error.permission_error'));
        }
        if ($user->disabled) {
            return ServiceReturn::error(message: __('common_error.permission_error'));
        }
        if (!in_array($roles, $user->role)) {
            return ServiceReturn::error(message: __('common_error.permission_error'));
        }
        return ServiceReturn::success();
    }

    /**
     * ---- Private methods ----
     */

     /**
     * Verify hash telegram (dựa trên document telegram)
     * @param array $authData
     * @return bool
     */
    private function verifyHashTelegramInternal(array $authData): bool
    {
        $checkHash = $authData['hash'] ?? '';
        unset($authData['hash']);

        $dataCheckArr = [];
        foreach ($authData as $key => $value) {
            if ($value) {
                $dataCheckArr[] = $key . '=' . $value;
            }
        }
        sort($dataCheckArr);

        $dataCheckString = implode("\n", $dataCheckArr);
        $secretKey = hash('sha256', config('services.telegram.bot_token'), true);
        $hash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if ((time() - ($authData['auth_date'] ?? 0)) > 86400) {
            return false;
        }
        $validate = hash_equals($hash, $checkHash);
        if (!$validate) {
            return false;
        }
        return true;
    }

}
