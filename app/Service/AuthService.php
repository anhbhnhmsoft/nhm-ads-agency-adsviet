<?php

namespace App\Service;

use App\Common\Constants\User\UserRole;
use App\Common\Helper;
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
     * @return ServiceReturn
     */
    public function handleLoginUsername(array $data): ServiceReturn
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

        // Kiểm tra username tồn tại trong hệ thống phù hợp với role
        try {
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
        } catch (QueryException $exception) {
            Logging::error(
                message: 'Lỗi kiểm tra username AuthService@handleLoginUsername: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }

        // Thực hiện đăng nhập
        if (Auth::guard('web')->attempt([
            'username' => $data['username'],
            'password' => $data['password'],
            'disabled' => false,
        ], true)) {
            try {
                // Lưu thông tin thiết bị đăng nhập website
                if ($data['device'] == "web") {
                    $this->userDeviceRepository->syncActiveUserWeb(Auth::id());
                }
                // mobile xử lý sau
            } catch (\Exception $exception) {
                Logging::error(
                    message: 'Lỗi khi xử lý sau đăng nhập AuthService@handleLoginUsername: ' . $exception->getMessage(),
                    exception: $exception
                );
                Auth::logout();
                return ServiceReturn::error(message: __('common_error.server_error'));
            }
            request()->session()->regenerate();
            return ServiceReturn::success();
        }
        return ServiceReturn::error(message: __('auth.login.validation.invalid_credentials'));
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
            $user = $this->userRepository->getUserByTelegramId($telegramId);
            if (!$user) {
                return ServiceReturn::success(data: [
                    'need_register' => true
                ]);
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

    public function handleRegisterNewUser(array $data): ServiceReturn
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
            // Tạo mới user
            $user = $this->userRepository->create($register);

            // Tạo mới user referral
            $this->userReferralRepository->create([
                'referrer_id' => $user->id,
                'referred_id' => $userRefer->id,
            ]);

            // Đăng nhập luôn
            Auth::guard('web')->login($user, true);
            $this->userDeviceRepository->syncActiveUserWeb($user->id);
            request()->session()->regenerate();
            DB::commit();
            return ServiceReturn::success();
        }catch (\Exception $exception) {
            Logging::error(
                message: 'Lỗi khi đăng ký AuthService@handleRegisterNewUser: ' . $exception->getMessage(),
                exception: $exception
            );
            DB::rollBack();
            Auth::logout();
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

}
