<?php

return [
    'name' => [
        'required' => 'Full name is required.',
        'string' => 'Full name must be a string.',
        'max' => 'Full name may not exceed :max characters.',
    ],
    'username' => [
        'required' => 'Username is required.',
        'string' => 'Username must be a string.',
        'max' => 'Username may not exceed :max characters.',
        'unique' => 'Username already exists, please choose another one.',
    ],
    'password' => [
        'required' => 'Password is required.',
        'regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
        'string' => 'Password is invalid.',
        'min' => 'Password must be at least :min characters.',
    ],
    'current_password' => [
        'required' => 'Current password is required.',
        'string' => 'Current password is invalid.',
    ],
    'new_password' => [
        'required' => 'New password is required.',
        'string' => 'New password is invalid.',
        'min' => 'New password must be at least :min characters.',
        'confirmed' => 'Password confirmation does not match.',
    ],
    'phone' => [
        'string' => 'Phone number is invalid.',
        'max' => 'Phone number may not exceed :max characters.',
        'unique' => 'Phone number already exists.',
    ],
    'email' => [
        'required' => 'Email is required.',
        'string' => 'Email must be a string.',
        'email' => 'Email is invalid.',
        'max' => 'Email may not exceed :max characters.',
        'unique' => 'Email already exists.',
    ],
    'role' => [
        'required' => 'Please choose a role.',
        'invalid' => 'Role is invalid.',
    ],
    'refer_code' => [
        'required' => 'Referral code is required.',
        'invalid' => 'Referral code is invalid.',
    ],
    'disabled' => [
        'required' => 'Status is required.',
        'boolean' => 'Status is invalid.',
    ],
    'otp_invalid' => 'OTP is invalid.',
    'user_id' => [
        'required' => 'User is required.',
        'string' => 'User must be a string.',
        'exists' => 'User does not exist.',
    ],
    'token_invalid' => 'Token is invalid.',
    'amount' => [
        'required' => 'Amount is required.',
        'numeric' => 'Amount must be numeric.',
        'gt' => 'Amount must be greater than 0.',
        'min' => 'Minimum amount is :min.',
    ],
    'network' => [
        'required' => 'Network is required.',
        'string' => 'Network must be a string.',
        'in' => 'Invalid network. Only BEP20 or TRC20 are accepted.',
    ],
];

