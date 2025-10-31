<?php

namespace App\Service;

use App\Core\ServiceReturn;
use App\Repositories\UserOtpRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Common\Constants\Otp\Otp;

class OtpService
{
    public function __construct(
        protected UserOtpRepository $userOtpRepository,
    ) {
    }

    //Hàm tạo mã OTP gầm 6 số
    public function generateOtp(Otp $type = Otp::VERIFY): ServiceReturn
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = Carbon::now()->addMinutes(5);

        try {
            $this->userOtpRepository->create([
                'user_id' => null,
                'code' => $code,
                'type' => $type->value,
                'expires_at' => $expiresAt,
            ]);
            return ServiceReturn::success(data: ['code' => $code, 'expires_at' => $expiresAt->toDateTimeString()]);
        } catch (\Throwable $e) {
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    //Kiểm tra mã OTP
    public function verifyOtp(string $code, Otp $type = Otp::VERIFY): ServiceReturn
    {
        $now = Carbon::now();
        $otp = DB::table('user_otp')
            ->where('code', $code)
            ->where('type', $type->value)
            ->where('expires_at', '>=', $now)
            ->orderByDesc('id')
            ->first();

        if (!$otp) {
            return ServiceReturn::error(message: __('auth.register.validation.otp_invalid'));
        }

        DB::table('user_otp')->where('id', $otp->id)->delete();

        return ServiceReturn::success();
    }
}
