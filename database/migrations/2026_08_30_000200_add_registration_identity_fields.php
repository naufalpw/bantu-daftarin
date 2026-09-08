<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_application_details', function (Blueprint $table): void {
            $table->text('nik')->nullable();
            $table->text('family_card_number')->nullable();
        });

        Schema::table('business_application_details', function (Blueprint $table): void {
            $table->text('business_type_other')->nullable();
        });

        $personalService = DB::table('services')->where('code', 'NPWP_PERSONAL')->first();
        if ($personalService && ! DB::table('service_requirements')->where('service_id', $personalService->id)->where('code', 'NPWP')->exists()) {
            DB::table('service_requirements')->insert([
                'public_id' => (string) Str::uuid(),
                'service_id' => $personalService->id,
                'code' => 'NPWP',
                'name' => 'NPWP (Jika ada)',
                'is_required' => false,
                'allowed_extensions' => json_encode(['jpg', 'jpeg', 'png', 'pdf'], JSON_THROW_ON_ERROR),
                'allowed_mimes' => json_encode(['image/jpeg', 'image/png', 'application/pdf'], JSON_THROW_ON_ERROR),
                'max_size_bytes' => 5 * 1024 * 1024,
                'condition' => null,
                'sort_order' => 30,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $personalService = DB::table('services')->where('code', 'NPWP_PERSONAL')->first();
        if ($personalService) {
            DB::table('service_requirements')
                ->where('service_id', $personalService->id)
                ->where('code', 'NPWP')
                ->delete();
        }

        Schema::table('personal_application_details', function (Blueprint $table): void {
            $table->dropColumn(['nik', 'family_card_number']);
        });

        Schema::table('business_application_details', function (Blueprint $table): void {
            $table->dropColumn('business_type_other');
        });
    }
};
