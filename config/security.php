<?php

return [
    'login' => [
        'max_attempts' => (int) env('AUTH_MAX_ATTEMPTS', 5),
        'lock_minutes' => (int) env('AUTH_LOCK_MINUTES', 15),
    ],

    'mfa' => [
        'expires_minutes' => (int) env('MFA_EXPIRES_MINUTES', 5),
        'max_attempts' => (int) env('MFA_MAX_ATTEMPTS', 5),
    ],
];
