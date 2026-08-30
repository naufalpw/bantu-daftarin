<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'PENDING';
    case PAID = 'PAID';
    case FAILED = 'FAILED';
    case EXPIRED = 'EXPIRED';
    case CANCELLED = 'CANCELLED';
    case REFUND_REQUESTED = 'REFUND_REQUESTED';
    case REFUNDING = 'REFUNDING';
    case REFUNDED = 'REFUNDED';

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, match ($this) {
            self::PENDING => [self::PAID, self::FAILED, self::EXPIRED, self::CANCELLED],
            self::PAID => [self::REFUND_REQUESTED],
            self::REFUND_REQUESTED => [self::REFUNDING, self::CANCELLED],
            self::REFUNDING => [self::REFUNDED],
            self::FAILED, self::EXPIRED, self::CANCELLED, self::REFUNDED => [],
        }, true);
    }
}
