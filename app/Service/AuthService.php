<?php

namespace App\Service;

use App\Core\Logging;
use App\Core\ServiceReturn;
use App\Repositories\UserDeviceRepository;
use App\Repositories\UserOtpRepository;
use App\Repositories\UserRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AuthService
{
    public function __construct(
        protected UserRepository $userRepository,
        protected UserOtpRepository $userOtpRepository,
        protected UserDeviceRepository $userDeviceRepository,
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
        }
        catch (QueryException $exception){
            Logging::error(
                message: 'Lỗi kiểm tra username AuthService@handleLoginUsername: '.$exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }

        // Thực hiện đăng nhập
        if (Auth::guard('web')->attempt([
            'username' => $data['username'],
            'password' => $data['password']
        ],true)) {
            try {
                // Lưu thông tin thiết bị đăng nhập website
                if ($data['device'] == "web"){
                    $this->userDeviceRepository->syncActiveUserWeb(Auth::id());
                }
                // mobile xử lý sau
            }catch (\Exception $exception){
                Logging::error(
                    message: 'Lỗi khi xử lý sau đăng nhập AuthService@handleLoginUsername: '.$exception->getMessage(),
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

}
