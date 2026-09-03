<?php

namespace App\Exceptions;

use RuntimeException;

class OtpChallengeException extends RuntimeException
{
    public function __construct(string $message, public readonly ?int $retryAfterSeconds = null)
    {
        parent::__construct($message);
    }

    public static function cooldown(int $seconds): self
    {
        $seconds = max(1, $seconds);

        return new self("Kode OTP baru belum dapat dikirim. Silakan tunggu {$seconds} detik lagi.", $seconds);
    }
}
