<?php

return [
    'login' => [
        'success' => 'Login successful.',
        'need_register' => 'Please register before logging in.',
        'validation' => [
            'rate_limit' => 'Invalid username or password. Please try again in :seconds seconds.',
            'invalid_credentials' => 'Invalid username or password.',
            'role.required' => 'Please choose a role.',
            'role.in' => 'The selected role is invalid.',
            'device_required' => 'Please choose a device.',
            'device_in' => 'The selected device is invalid.',
            'telegram_hash_invalid' => 'Telegram information is invalid. Please check again.',
            'choose_social_first' => 'Please choose a previous login method.',
            'device_id_required' => 'Please enter the device ID.',
            'device_name_string' => 'Device name must be a string.',
            'device_name_max' => 'Device name may not be greater than :max characters.',
            'user_disabled' => 'Your account has been locked. Please contact the administrator.',
            'email_not_verified' => 'Your email has not been verified. Please verify it before logging in.',
            'user_not_allowed' => 'Your account cannot access this system.',
        ],
    ],
    'register' => [
        'success' => 'Registration successful. Please check your email for the OTP code.',
        'email_otp_sent' => 'Verification code sent to :email.',
        'email_otp_failed' => 'Cannot send verification code. Please try again later.',
        'email_otp_invalid' => 'Invalid verification code.',
        'email_otp_expired' => 'Verification code has expired.',
        'email_otp_mismatch' => 'Email does not match the verification request.',
        'validation' => [
            'role_required' => 'Please choose a role.',
            'role_in' => 'The selected role is invalid.',
            'refer_code_required' => 'Please enter a referral code.',
            'refer_code_string' => 'Referral code must be a string.',
            'refer_code_invalid' => 'Referral code is invalid.',
            'token_invalid' => 'Registration token is invalid.',
        ],
    ],
    'verify_register' => [
        'success' => 'Registration verified successfully.',
        'validation' => [
            'otp.required' => 'Please enter the OTP.',
            'otp.string' => 'OTP must be a string.',
            'otp.max' => 'OTP may not be greater than :max characters.',
        ],
    ],
    'forgot_password' => [
        'success' => 'The new password has been sent to your Telegram.',
        'otp' => "Forgot password OTP\n\nHello 👋\nYour password reset OTP is: :otp\n\nThis OTP will expire in :expire_time minutes.\n\nIf this was not you, please ignore this message.",
        'validation' => [
            'user_exists' => 'Account does not exist.',
            'social_or_email_verify' => 'This account has not been verified. Please verify before requesting a password reset.',
        ],
        'error' => [
            'error_send_otp' => 'Cannot send OTP. Please try again later.',
        ],
    ],
    'verify_forgot_password' => [
        'success' => 'Password updated successfully.',
        'validation' => [
            'otp_invalid' => 'OTP is invalid.',
        ],
    ],
];

