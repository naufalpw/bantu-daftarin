<?php

return [
    'expire_minutes' => (int) env('OTP_EXPIRE_MINUTES', 10),
    'resend_cooldown_seconds' => (int) env('OTP_RESEND_COOLDOWN_SECONDS', 60),
    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
];
