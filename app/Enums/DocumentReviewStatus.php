<?php

namespace App\Enums;

enum DocumentReviewStatus: string
{
    case PENDING = 'PENDING';
    case ACCEPTED = 'ACCEPTED';
    case REJECTED = 'REJECTED';
    case REVISION_REQUIRED = 'REVISION_REQUIRED';
    case LOCKED = 'LOCKED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu pemeriksaan',
            self::ACCEPTED => 'Diterima',
            self::REJECTED => 'Ditolak',
            self::REVISION_REQUIRED => 'Perlu perbaikan dokumen',
            self::LOCKED => 'Terkunci',
        };
    }
}
