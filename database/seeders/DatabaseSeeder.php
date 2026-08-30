<?php

namespace Database\Seeders;

use App\Enums\ServiceStatus;
use App\Enums\UserRole;
use App\Models\Admin;
use App\Models\Service;
use App\Models\ServiceRequirement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminPassword = (string) config('bantu.admin.seed_password');
        if (blank($adminPassword) || strlen($adminPassword) < 16) {
            throw new \RuntimeException('ADMIN_SEED_PASSWORD wajib diisi dan minimal 16 karakter sebelum menjalankan seeder.');
        }

        $adminUser = User::query()->firstOrNew(['email' => strtolower((string) config('bantu.admin.email'))]);
        $adminUser->forceFill([
            'name' => config('bantu.admin.name'),
            'password' => Hash::make($adminPassword),
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ])->save();
        Admin::updateOrCreate(
            ['user_id' => $adminUser->getKey()],
            ['email' => $adminUser->email, 'role' => UserRole::SUPER_ADMIN, 'is_active' => true]
        );

        $personal = Service::updateOrCreate(
            ['code' => 'NPWP_PERSONAL'],
            [
                'name' => 'NPWP Perseorangan',
                'description' => 'Bantuan administratif pengurusan NPWP untuk perseorangan.',
                'status' => ServiceStatus::ACTIVE,
                // [BUSINESS CONFIRMATION REQUIRED] replace development price before production.
                'price_amount' => config('bantu.service_prices.NPWP_PERSONAL'),
                'currency' => 'IDR',
                'sort_order' => 10,
                'activated_at' => now(),
            ]
        );
        $business = Service::updateOrCreate(
            ['code' => 'NPWP_BUSINESS'],
            [
                'name' => 'NPWP Badan Usaha',
                'description' => 'Bantuan administratif pengurusan NPWP untuk badan usaha.',
                'status' => ServiceStatus::ACTIVE,
                // [BUSINESS CONFIRMATION REQUIRED] replace development price before production.
                'price_amount' => config('bantu.service_prices.NPWP_BUSINESS'),
                'currency' => 'IDR',
                'sort_order' => 20,
                'activated_at' => now(),
            ]
        );
        $comingSoon = Service::updateOrCreate(
            ['code' => 'TAX_REPORTING'],
            ['name' => 'Lapor Pajak', 'description' => 'Layanan ini segera hadir.', 'status' => ServiceStatus::COMING_SOON, 'price_amount' => null, 'currency' => 'IDR', 'sort_order' => 90, 'activated_at' => null]
        );

        $this->seedRequirements($personal, [
            ['code' => 'KTP', 'name' => 'KTP', 'required' => true, 'max' => 5],
            ['code' => 'KK', 'name' => 'Kartu Keluarga', 'required' => true, 'max' => 5],
            ['code' => 'NPWP', 'name' => 'NPWP (Jika ada)', 'required' => false, 'max' => 5],
            ['code' => 'FOTO_WAJAH', 'name' => 'Foto wajah', 'required' => true, 'max' => 5],
        ]);
        $this->seedRequirements($business, [
            ['code' => 'KTP_PENANGGUNG_JAWAB', 'name' => 'KTP penanggung jawab utama', 'required' => true, 'max' => 5],
            ['code' => 'AKTA_NOTARIS', 'name' => 'Akta notaris', 'required' => true, 'max' => 15],
            ['code' => 'SK_AHU', 'name' => 'SK AHU', 'required' => true, 'max' => 10],
            ['code' => 'SURAT_KUASA', 'name' => 'Surat kuasa', 'required' => false, 'max' => 5, 'condition' => 'jika diwakilkan'],
        ]);
        // Keep the coming-soon service without active requirements or price.
        $comingSoon->requirements()->update(['active' => false]);
    }

    private function seedRequirements(Service $service, array $requirements): void
    {
        foreach ($requirements as $index => $item) {
            ServiceRequirement::updateOrCreate(
                ['service_id' => $service->getKey(), 'code' => $item['code']],
                [
                    'name' => $item['name'],
                    'is_required' => $item['required'],
                    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf'],
                    'allowed_mimes' => ['image/jpeg', 'image/png', 'application/pdf'],
                    'max_size_bytes' => $item['max'] * 1024 * 1024,
                    'condition' => $item['condition'] ?? null,
                    'sort_order' => ($index + 1) * 10,
                    'active' => true,
                ]
            );
        }
    }
}
