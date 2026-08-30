<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case BCA = 'BCA';
    case BRI = 'BRI';
    case QRIS = 'QRIS';
    case PAYPAL = 'PAYPAL';

    public static function values(): array
    {
        return array_map(static fn (self $method): string => $method->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::BCA => 'BCA',
            self::BRI => 'BRI',
            self::QRIS => 'QRIS',
            self::PAYPAL => 'PayPal',
        };
    }

    public function provider(): string
    {
        return $this === self::PAYPAL ? 'paypal' : 'xendit';
    }

    public function xenditChannelCode(): ?string
    {
        return match ($this) {
            self::BCA => 'BCA_VIRTUAL_ACCOUNT',
            self::BRI => 'BRI_VIRTUAL_ACCOUNT',
            self::QRIS => 'QRIS',
            self::PAYPAL => null,
        };
    }

    public function isAvailable(): bool
    {
        return $this !== self::PAYPAL;
    }
}
