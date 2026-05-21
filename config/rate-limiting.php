<?php

return [

    'login' => [
        'max_attempts' => (int) env('RATE_LIMIT_LOGIN', 5),
        'decay_minutes' => (int) env('RATE_LIMIT_LOGIN_DECAY', 1),
    ],

    'register' => [
        'max_attempts' => (int) env('RATE_LIMIT_REGISTER', 3),
        'decay_minutes' => (int) env('RATE_LIMIT_REGISTER_DECAY', 60),
    ],

    'otp_send' => [
        'per_email' => (int) env('RATE_LIMIT_OTP_SEND_EMAIL', 3),
        'per_ip' => (int) env('RATE_LIMIT_OTP_SEND_IP', 10),
        'decay_minutes' => (int) env('RATE_LIMIT_OTP_SEND_DECAY', 1),
    ],

    'otp_verify' => [
        'max_attempts' => (int) env('RATE_LIMIT_OTP_VERIFY', 10),
        'decay_minutes' => (int) env('RATE_LIMIT_OTP_VERIFY_DECAY', 1),
    ],

    'reset_password' => [
        'max_attempts' => (int) env('RATE_LIMIT_RESET_PASSWORD', 5),
        'decay_minutes' => (int) env('RATE_LIMIT_RESET_PASSWORD_DECAY', 1),
    ],

    'refresh' => [
        'max_attempts' => (int) env('RATE_LIMIT_REFRESH', 30),
        'decay_minutes' => (int) env('RATE_LIMIT_REFRESH_DECAY', 1),
    ],

    'logout' => [
        'max_attempts' => (int) env('RATE_LIMIT_LOGOUT', 60),
        'decay_minutes' => (int) env('RATE_LIMIT_LOGOUT_DECAY', 1),
    ],

];
