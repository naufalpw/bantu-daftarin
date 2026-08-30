<?php

namespace App\Enums;

enum BusinessType: string
{
    case INTERNATIONAL_BODY = 'BADAN_INTERNASIONAL';
    case OTHER_LEGAL_FORM = 'LEMBAGA_DAN_BENTUK_BADAN_LAINNYA';
    case EVENT_ORGANIZER = 'PENYELENGGARA_KEGIATAN';
    case FOREIGN_COMPANY_REPRESENTATIVE = 'KPPA';
    case LIMITED_LIABILITY_COMPANY = 'PT';
    case VILLAGE_OWNED_ENTERPRISE = 'BUMDES';
    case JOINT_OPERATION = 'KSO_JO';
    case OTHER_ORGANIZATION = 'ORGANISASI_LAINNYA';
    case ASSOCIATION = 'PERKUMPULAN';
    case PUBLIC_COMPANY = 'PERUM';
    case PERMANENT_ESTABLISHMENT = 'BUT';
    case PARTNERSHIP = 'KONGSI';
    case MASS_ORGANIZATION = 'ORGANISASI_MASSA';
    case CIVIL_PARTNERSHIP = 'PERSEKUTUAN_PERDATA';
    case FOREIGN_STATE_REPRESENTATIVE = 'PERWAKILAN_NEGARA_ASING';
    case PENSION_FUND = 'DANA_PENSIUN';
    case COLLECTIVE_INVESTMENT_CONTRACT = 'KIK';
    case SOCIAL_POLITICAL_ORGANIZATION = 'ORGANISASI_SOSIAL_POLITIK';
    case LIMITED_PARTNERSHIP = 'CV';
    case FOUNDATION = 'YAYASAN';
    case FIRMA = 'FIRMA';
    case COOPERATIVE_INVESTMENT_CONTRACT = 'KIK_KOPERASI';
    case INDIVIDUAL_COMPANY = 'PT_PERORANGAN';
    case OTHER_COMPANY = 'PERSEROAN_LAINNYA';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::INTERNATIONAL_BODY => 'Badan Internasional',
            self::OTHER_LEGAL_FORM => 'Lembaga dan bentuk badan lainnya',
            self::EVENT_ORGANIZER => 'Penyelnggara Kegiatan',
            self::FOREIGN_COMPANY_REPRESENTATIVE => 'Kantor Perwakilan perusahaan Asing (KPPA)',
            self::LIMITED_LIABILITY_COMPANY => 'Perseroan Terbatas (PT)',
            self::VILLAGE_OWNED_ENTERPRISE => 'Badan Usaha Milik Desa',
            self::JOINT_OPERATION => 'Kerja Sama Operasi (KSO/JO)',
            self::OTHER_ORGANIZATION => 'Organisasi lainnya',
            self::ASSOCIATION => 'Perkumpulan',
            self::PUBLIC_COMPANY => 'Perusahaan Umum',
            self::PERMANENT_ESTABLISHMENT => 'Bentuk Usaha tetap (BUT)',
            self::PARTNERSHIP => 'Kongsi',
            self::MASS_ORGANIZATION => 'Organisasi Massa',
            self::CIVIL_PARTNERSHIP => 'Persekutuan Perdata',
            self::FOREIGN_STATE_REPRESENTATIVE => 'Perwakilan Negara Asing',
            self::PENSION_FUND => 'Dana Pensiun',
            self::COLLECTIVE_INVESTMENT_CONTRACT => 'Kontak Investasi Kolektif',
            self::SOCIAL_POLITICAL_ORGANIZATION => 'Organisasi sosial Poitik',
            self::LIMITED_PARTNERSHIP => 'Perseroan Komanditer (CV)',
            self::FOUNDATION => 'Yayasan',
            self::FIRMA => 'Firma',
            self::COOPERATIVE_INVESTMENT_CONTRACT => 'Kontak Investasi Koperasi',
            self::INDIVIDUAL_COMPANY => 'PT Perorangan',
            self::OTHER_COMPANY => 'Perseroan lainnya',
            self::OTHER => 'LAINNYA',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }
}
