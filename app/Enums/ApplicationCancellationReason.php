<?php

namespace App\Enums;

enum ApplicationCancellationReason: string
{
    case WRONG_SERVICE = 'WRONG_SERVICE';
    case START_NEW_APPLICATION = 'START_NEW_APPLICATION';
    case REENTER_DATA = 'REENTER_DATA';
    case NO_LONGER_CONTINUE = 'NO_LONGER_CONTINUE';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::WRONG_SERVICE => 'Salah memilih layanan',
            self::START_NEW_APPLICATION => 'Ingin membuat pengajuan baru',
            self::REENTER_DATA => 'Data perlu diisi ulang',
            self::NO_LONGER_CONTINUE => 'Tidak ingin melanjutkan',
            self::OTHER => 'Lainnya',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $reason): array => [$reason->value => $reason->label()])
            ->all();
    }
}
