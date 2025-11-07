<?php

namespace App\Service;

use App\Common\Constants\DeviceType;
use App\Common\Constants\User\UserRole;
use App\Common\Helper;
use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\Logging;
use App\Core\ServiceReturn;
use App\Models\User;
use App\Repositories\UserDeviceRepository;
use App\Repositories\UserOtpRepository;
use App\Repositories\UserReferralRepository;
use App\Repositories\UserRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthService
{
    public function __construct(
        protected UserRepository       $userRepository,
        protected UserOtpRepository    $userOtpRepository,
        protected UserDeviceRepository $userDeviceRepository,
        protected UserReferralRepository $userReferralRepository,
    )
    {
    }

    /**
     * Handle login with username
     * @param array $data
     * @param bool $isMobile
     * @return ServiceReturn
     */
    public function handleLoginUsername(array $data, bool $isMobile = false): ServiceReturn
    {
        // Kiểm tra giới hạn đăng nhập
        $key = 'login.attempts.' . request()->ip();
        $maxAttempts = 5; // số lần đăng nhập thất bại trong 1 khoảng thời gian
        $decayMinutes = 1; // số phút
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            return ServiceReturn::error(message: __('auth.login.validation.rate_limit', ['seconds' => $seconds]));
        }
        RateLimiter::hit($key, $decayMinutes * 60);


        try {
            // Kiểm tra username tồn tại trong hệ thống phù hợp với role
            if (!$isMobile) {
                if ($data['role'] == 'admin') {
                    // Kiểm tra username trong hệ thống admin
                    if (!$this->userRepository->checkUsernameAdminSystem($data['username'])) {
                        return ServiceReturn::error(message: __('auth.login.validation.invalid_credentials'));
                    }
                } else {
                    // Kiểm tra username trong hệ thống khách hàng
                    if (!$this->userRepository->checkUsernameCustomerSystem($data['username'])) {
                        return ServiceReturn::error(message: __('auth.login.validation.invalid_credentials'));
                    }
                }
            }else{
                // mobile chỉ dùng cho khách hàng
                // Kiểm tra username trong hệ thống khách hàng
                if (!$this->userRepository->checkUsernameCustomerSystem($data['username'])) {
                    return ServiceReturn::error(message: __('auth.login.validation.invalid_credentials'));
                }
            }
        } catch (QueryException $exception) {
            Logging::error(
                message: 'Lỗi kiểm tra username AuthService@handleLoginUsername: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }

        try {
            // --- LOGIN API (Sanctum) ---
            if ($isMobile) {
                /**
                 * @var User|null $user
                 */
                $user = $this->userRepository->query()
                    ->where('username', $data['username'])
                    ->where('disabled', false)
                    ->first();

                if (!$user || !Hash::check($data['password'], $user->password)) {
                    return ServiceReturn::error(message: __('auth.login.validation.invalid_credentials'));
                }

                // Đồng bộ thiết bị đăng nhập
                $this->userDeviceRepository->syncActiveUserMobile(
                    userId: $user->id,
                    deviceId: $data['device_id'],
                    deviceName: $data['device_name'] ?? null,
                    deviceType: $data['platform'] === 'ios' ? DeviceType::IOS : DeviceType::ANDROID,
                );

                // Tạo token Sanctum
                $token = $user->createToken(
                    name:'api-token',
                    expiresAt: $data['remember_me'] ? now()->addDays(30) : null
                )->plainTextToken;

                return ServiceReturn::success(data: [
                    'token' => $token,
                    'user' => $user,
                ]);
            }

            // --- LOGIN WEB (Session) ---
            if (Auth::guard('web')->attempt([
                'username' => $data['username'],
                'password' => $data['password'],
                'disabled' => false,
            ], $data['remember_me'] ?? false)) {

                $this->userDeviceRepository->syncActiveUserWeb(Auth::id());

                request()->session()->regenerate();

                return ServiceReturn::success();
            }

            return ServiceReturn::error(message: __('auth.login.validation.invalid_credentials'));
        }
        catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi khi xử lý sau đăng nhập AuthService@handleLoginUsername: ' . $exception->getMessage(),
                exception: $exception
            );

            // Logout / Revoke token nếu lỗi
            if ($isMobile && isset($user)) {
                $user->currentAccessToken()?->delete();
            } else {
                Auth::guard('web')->logout();
            }

            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function handleLogout(): ServiceReturn
    {
        Auth::logout();
        Caching::flushCacheSession();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return ServiceReturn::success();
    }

    /**
     * Verify login with telegram
     * @param array $authData
     * @return ServiceReturn
     */
    public function verifyHashTelegram(array $authData): ServiceReturn
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

        // Kiểm tra auth_date (không quá 24h)
        if ((time() - ($authData['auth_date'] ?? 0)) > 86400) {
            return ServiceReturn::error(message: __('auth.login.validation.telegram_hash_invalid'));
        }
        $validate = hash_equals($hash, $checkHash);
        if (!$validate) {
            return ServiceReturn::error(message: __('auth.login.validation.telegram_hash_invalid'));
        }

        return ServiceReturn::success();
    }

    /**
     * Quick login with telegram id
     * @param string $telegramId
     * @return ServiceReturn
     */
    public function handleQuickLoginTelegram(string $telegramId): ServiceReturn
    {
        try {
            // Web cho phép đăng nhập với admin system
            $user = $this->userRepository->getUserByTelegramId($telegramId, false, true);
            if (!$user) {
                return ServiceReturn::success(data: [
                    'need_register' => true
                ]);
            }
            // Kiểm tra user có bị khóa hay không
            if ($user->disabled) {
                return ServiceReturn::error(message: __('auth.login.validation.user_disabled'));
            }

            // Thực hiện đăng nhập
            Auth::guard('web')->login($user, true);
            // Lưu thông tin thiết bị đăng nhập website
            $this->userDeviceRepository->syncActiveUserWeb($user->id);
            request()->session()->regenerate();
            return ServiceReturn::success(data: [
                'need_register' => false
            ]);
        } catch (QueryException $exception) {
            Logging::error(
                message: 'Lỗi kiểm tra telegram id AuthService@handleLoginTelegram: ' . $exception->getMessage(),
                exception: $exception
            );
            Auth::logout();
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * @param array $authData
     * @return ServiceReturn
     */
    public function handleAuthTelegram(array $authData): ServiceReturn
    {
        try {
            // Kiểm tra user có tồn tại trong hệ thống hay không
            // Kiểm tra user có phải là role AGENCY hoặc CUSTOMER hay không
            $user = $this->userRepository->getUserByTelegramId($authData['id'], false, false);
            if (!$user) {
                return ServiceReturn::success(data: [
                    'need_register' => true
                ]);
            }
            // Kiểm tra user có bị khóa hay không
            if ($user->disabled) {
                return ServiceReturn::error(message: __('auth.login.validation.user_disabled'));
            }
            // Đồng bộ thiết bị đăng nhập
            $this->userDeviceRepository->syncActiveUserMobile(
                userId: $user->id,
                deviceId: $authData['device_id'],
                deviceName: $authData['device_name'] ?? null,
                deviceType: $authData['platform'] === 'ios' ? DeviceType::IOS : DeviceType::ANDROID,
            );
            // Tạo token Sanctum
            $token = $user->createToken('api-token')->plainTextToken;

            return ServiceReturn::success(data: [
                'need_register' => false,
                'token' => $token,
                'user' => $user,
            ]);

        } catch (QueryException $exception) {
            Logging::error(
                message: 'Lỗi kiểm tra telegram id AuthService@handleAuthTelegram: ' . $exception->getMessage(),
                exception: $exception
            );
            Auth::logout();
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Xử lý đăng ký mới khi user chưa có trong hệ thống
     * @param array $data
     * @param bool $isMobile
     * @return ServiceReturn
     */
    public function handleRegisterNewUser(array $data, bool $isMobile = false): ServiceReturn
    {
        DB::beginTransaction();
        try {
            // Kiểm tra refer code có tồn tại trong hệ thống hay không
            $userRefer = $this->userRepository->getUserToRegisterByReferCode($data['refer_code']);
            if (!$userRefer) {
                return ServiceReturn::error(message: __('auth.register.validation.invalid'));
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
            ];
            if ($data['type'] == 'telegram') {
                $register['telegram_id'] = $data['telegram_id'];
            }
            $user = $this->userRepository->create($register);

            // Tạo mới user referral
            $this->userReferralRepository->create([
                'referrer_id' => $user->id,
                'referred_id' => $userRefer->id,
            ]);
            if ($isMobile) {
                // Đồng bộ thiết bị đăng nhập
                $this->userDeviceRepository->syncActiveUserMobile(
                    userId: $user->id,
                    deviceId: $data['device_id'],
                    deviceName: $data['device_name'] ?? null,
                    deviceType: $data['platform'] === 'ios' ? DeviceType::IOS : DeviceType::ANDROID,
                );

                // Tạo token Sanctum
                $token = $user->createToken('api-token')->plainTextToken;
                DB::commit();
                return ServiceReturn::success(data: [
                    'token' => $token,
                    'user' => $user,
                ]);
            }else{
                // Đăng nhập luôn
            /** @var \App\Models\User $user */
                Auth::guard('web')->login($user, true);
                $this->userDeviceRepository->syncActiveUserWeb($user->id);
                DB::commit();
                request()->session()->regenerate();
                return ServiceReturn::success();
            }
        }catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi khi đăng ký AuthService@handleRegisterNewUser: ' . $exception->getMessage(),
                exception: $exception
            );
            DB::rollBack();
            // Logout / Revoke token nếu lỗi
            if ($isMobile && isset($user)) {
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
     * Tìm user agency hoặc customer có telegram id
     * @param string $username
     * @return ServiceReturn
     */
    public function findCustomerAgencyHasTelegram(string $username): ServiceReturn
    {
        try {
            $user = $this->userRepository->filterQuery([
                'username' => $username,
                'has_telegram' => true,
                'roles' => [UserRole::AGENCY->value, UserRole::CUSTOMER->value],
                'is_active' => true,
            ])->first();
            if (!$user) {
                return ServiceReturn::error(message: __('auth.forgot_password.validation.user_exists'));
            }
            return ServiceReturn::success(data: $user);
        }catch (\Exception $exception) {
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
            $user = $this->findCustomerAgencyHasTelegram($data['username']);
            if ($user->isError()) {
                return ServiceReturn::error(message: $user->getMessage());
            }
            /**
             * @var User $user
             */
            $user = $user->getData();

            // Kiểm tra OTP
            $cacheOtp = Caching::getCache(
                key: CacheKey::CACHE_TELEGRAM_OTP,
                uniqueKey: $user->telegram_id,
            );
            if (!$cacheOtp || $cacheOtp != $data['code']) {
                return ServiceReturn::error(message: __('auth.verify_forgot_password.validation.otp_invalid'));
            }
            // Cập nhật password
            $this->userRepository->query()
                ->where('id', $user->id)
                ->update(['password' => Hash::make($data['password'])]);
            Caching::clearCache(key: CacheKey::CACHE_TELEGRAM_OTP, uniqueKey: $user->telegram_id);
            return ServiceReturn::success();
        }catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi khi xác nhận reset password AuthService@handleVerifyForgotPassword: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: $exception->getMessage());
        }
    }
}
