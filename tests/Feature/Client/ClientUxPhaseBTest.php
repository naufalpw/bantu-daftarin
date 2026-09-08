<?php

namespace Tests\Feature\Client;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentReviewStatus;
use App\Enums\DocumentScanStatus;
use App\Enums\ResultDocumentType;
use App\Enums\ResultVerificationStatus;
use App\Enums\ServiceStatus;
use App\Enums\UserRole;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ApplicationRequirement;
use App\Models\ApplicationStatusHistory;
use App\Models\ChatThread;
use App\Models\Document;
use App\Models\ResultDocument;
use App\Models\Service;
use App\Models\ServiceRequirement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ClientUxPhaseBTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_has_useful_empty_state_and_no_generic_metrics(): void
    {
        $user = User::factory()->create(['name' => 'Klien Sintetis']);

        $this->actingAs($user)->get(route('client.dashboard'))
            ->assertOk()
            ->assertSee('Belum ada pengajuan')
            ->assertSee('Lihat layanan')
            ->assertDontSee('Langkah berikutnya')
            ->assertDontSee('Pembaruan terbaru')
            ->assertDontSee('Total pengajuan')
            ->assertDontSee('KPI');
    }

    public function test_dashboard_surfaces_the_most_important_real_next_action(): void
    {
        $user = User::factory()->create();
        $service = $this->service('NPWP_PERSONAL', 'NPWP Perseorangan', 175000);
        $application = $this->application($user, $service, ApplicationStatus::REVISION_REQUIRED);

        $this->actingAs($user)->get(route('client.dashboard'))
            ->assertOk()
            ->assertSee('Langkah berikutnya')
            ->assertSee('Dokumen perlu diperbaiki')
            ->assertSee('Perbaiki dokumen')
            ->assertSee(route('client.applications.show', $application->public_id).'#data-dokumen', false);
    }

    public function test_services_resume_an_active_application_and_render_backend_price(): void
    {
        $user = User::factory()->create();
        $service = $this->service('NPWP_BUSINESS', 'NPWP Badan Usaha', 225000);
        $application = $this->application($user, $service, ApplicationStatus::DRAFT);

        $this->actingAs($user)->get(route('client.services.index'))
            ->assertOk()
            ->assertSee('IDR 225.000')
            ->assertSee('Lanjutkan pengajuan')
            ->assertSee(route('client.applications.show', $application->public_id), false)
            ->assertDontSee('Buat pengajuan baru');
    }

    public function test_services_catalog_resumes_an_active_personal_application(): void
    {
        $user = User::factory()->create();
        $service = $this->service('NPWP_PERSONAL', 'NPWP Perseorangan', 150000);
        $application = $this->application($user, $service, ApplicationStatus::AWAITING_DOCUMENTS);

        $this->actingAs($user)->get(route('client.services.index'))
            ->assertOk()
            ->assertSee('NPWP Perseorangan')
            ->assertSee('Pengajuan aktif')
            ->assertSee('Lengkapi dokumen')
            ->assertSee(route('client.applications.show', $application->public_id), false);
    }

    public function test_services_catalog_compares_active_services_and_keeps_tax_reporting_non_actionable(): void
    {
        $user = User::factory()->create();
        $personal = $this->service('NPWP_PERSONAL', 'NPWP Perseorangan', 150000);
        $business = $this->service('NPWP_BUSINESS', 'NPWP Badan Usaha', 500000);
        $comingSoon = Service::factory()->create([
            'code' => 'TAX_REPORTING',
            'name' => 'Lapor Pajak',
            'status' => ServiceStatus::COMING_SOON,
            'price_amount' => null,
        ]);

        ServiceRequirement::factory()->create([
            'service_id' => $personal->id,
            'code' => 'KK',
            'name' => 'Kartu Keluarga',
        ]);
        ServiceRequirement::factory()->create([
            'service_id' => $business->id,
            'code' => 'AKTA_NOTARIS',
            'name' => 'Akta notaris',
        ]);

        $this->actingAs($user)->get(route('client.services.index'))
            ->assertOk()
            ->assertSee('Pilih layanan')
            ->assertSee('Bandingkan persyaratan sebelum memulai pengajuan.')
            ->assertSee('IDR 150.000')
            ->assertSee('IDR 500.000')
            ->assertSee('Kartu Keluarga')
            ->assertSee('Akta notaris')
            ->assertSee('Lihat persyaratan')
            ->assertSee(route('client.applications.create', $personal->public_id), false)
            ->assertSee(route('client.applications.create', $business->public_id), false)
            ->assertSee('Lapor Pajak')
            ->assertSee('Segera hadir')
            ->assertDontSee('Belum tersedia')
            ->assertDontSee(route('client.applications.create', $comingSoon->public_id), false);
    }

    public function test_pengajuan_index_groups_real_states_without_fake_metrics(): void
    {
        $user = User::factory()->create();
        $service = $this->service();
        $this->application($user, $service, ApplicationStatus::AWAITING_DOCUMENTS);
        $this->application($user, $service, ApplicationStatus::UNDER_REVIEW);
        $this->application($user, $service, ApplicationStatus::COMPLETED);

        $this->actingAs($user)->get(route('client.applications.index'))
            ->assertOk()
            ->assertSee('Perlu tindakan')
            ->assertSee('Selesai')
            ->assertSee('Semua')
            ->assertSee('Diproses')
            ->assertSee('Lengkapi dokumen')
            ->assertSee('Dokumen sedang diperiksa')
            ->assertSee('Pengajuan selesai')
            ->assertDontSee('pb-application-summary', false)
            ->assertDontSee('Total transaksi');

        $this->actingAs($user)->get(route('client.applications.index', ['status' => 'processing']))
            ->assertOk()
            ->assertSee('Dokumen sedang diperiksa')
            ->assertDontSee('Lengkapi dokumen')
            ->assertDontSee('Pengajuan selesai');
    }

    public function test_dashboard_uses_owned_status_history_for_recent_activity(): void
    {
        $user = User::factory()->create();
        $application = $this->application($user, $this->service(), ApplicationStatus::UNDER_REVIEW);
        ApplicationStatusHistory::create([
            'application_id' => $application->id,
            'to_status' => ApplicationStatus::UNDER_REVIEW,
            'actor_type' => 'admin',
            'created_at' => now(),
        ]);

        $this->actingAs($user)->get(route('client.dashboard'))
            ->assertOk()
            ->assertSee('Aktivitas pengajuan')
            ->assertSee('Dokumen sedang diperiksa')
            ->assertSee($application->service->name)
            ->assertSee(route('client.applications.index'), false)
            ->assertDontSee('Buka bantuan');
    }

    public function test_personal_workspace_prioritizes_documents_and_denies_foreign_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $application = $this->application($owner, $this->service(), ApplicationStatus::DRAFT);

        $this->actingAs($owner)->get(route('client.applications.show', $application->public_id))
            ->assertOk()
            ->assertSee('Ruang pengajuan')
            ->assertSee('Tahap 1 dari 6')
            ->assertSee('Ringkasan')
            ->assertSee('Data &amp; Dokumen', false)
            ->assertSee('File disimpan secara privat')
            ->assertSee('Butuh bantuan?')
            ->assertSee('Bantuan umum tersedia di Pusat Bantuan.')
            ->assertSee('pb-sidebar-summary', false)
            ->assertSee('Lanjutkan pengajuan')
            ->assertDontSee('Bagian pengajuan')
            ->assertDontSee('Status dan rincian pembayaran')
            ->assertDontSee('Riwayat pengajuan')
            ->assertDontSee('Dokumen hasil terverifikasi');

        $this->actingAs($other)->get(route('client.applications.show', $application->public_id))->assertNotFound();
        $this->actingAs($other)->get(route('client.activity.show', $application->public_id))->assertForbidden();
    }

    public function test_personal_workspace_keeps_later_stage_sections_when_they_are_useful(): void
    {
        $user = User::factory()->create();
        $service = $this->service();
        $paymentApplication = $this->application($user, $service, ApplicationStatus::AWAITING_PAYMENT);
        $processApplication = $this->application($user, $service, ApplicationStatus::UNDER_REVIEW);
        $resultApplication = $this->application($user, $service, ApplicationStatus::COMPLETED);

        $this->actingAs($user)->get(route('client.applications.show', $paymentApplication->public_id))
            ->assertOk()
            ->assertSee('Status dan rincian pembayaran');

        $this->actingAs($user)->get(route('client.applications.show', $processApplication->public_id))
            ->assertOk()
            ->assertSee('Riwayat pengajuan');

        $this->actingAs($user)->get(route('client.applications.show', $resultApplication->public_id))
            ->assertOk()
            ->assertSee('Dokumen hasil terverifikasi');
    }

    public function test_document_revision_reason_and_instruction_are_visible(): void
    {
        $user = User::factory()->create();
        $application = $this->application($user, $this->service(), ApplicationStatus::REVISION_REQUIRED);
        $requirement = ApplicationRequirement::factory()->create([
            'application_id' => $application->id,
            'name' => 'KTP Penanggung Jawab',
            'status' => 'REVISION_REQUIRED',
        ]);
        Document::factory()->create([
            'application_id' => $application->id,
            'application_requirement_id' => $requirement->id,
            'uploaded_by_user_id' => $user->id,
            'scan_status' => DocumentScanStatus::PASSED,
            'review_status' => DocumentReviewStatus::REVISION_REQUIRED,
            'rejection_reason' => 'Foto terlalu gelap.',
            'revision_instruction' => 'Unggah ulang foto yang terbaca jelas.',
        ]);

        $this->actingAs($user)->get(route('client.applications.show', $application->public_id))
            ->assertOk()
            ->assertSee('Dokumen perlu diperbaiki')
            ->assertSee('Foto terlalu gelap.')
            ->assertSee('Unggah ulang foto yang terbaca jelas.')
            ->assertSee('Pastikan semua perbaikan sudah selesai sebelum dikirim.')
            ->assertSee('Ganti dokumen');
    }

    public function test_personal_workspace_uses_document_cards_and_a_face_photo_fallback(): void
    {
        $user = User::factory()->create();
        $application = $this->application($user, $this->service(), ApplicationStatus::AWAITING_DOCUMENTS);

        $requirements = $this->personalRequirements($application);
        $faceRequirement = $requirements->firstWhere('code', 'FOTO_WAJAH');

        $this->actingAs($user)->get(route('client.applications.show', $application->public_id))
            ->assertOk()
            ->assertSee('Dokumen Pengajuan')
            ->assertSee('Unggah foto atau salinan KTP.')
            ->assertSee('Unggah Kartu Keluarga.')
            ->assertSee('NPWP (Jika ada)')
            ->assertSee('Opsional')
            ->assertSee('Unggah KTP')
            ->assertSee('Unggah KK')
            ->assertSee('Unggah NPWP')
            ->assertSee('Foto Wajah')
            ->assertSee('Tips foto')
            ->assertDontSee('Tips Foto yang Baik')
            ->assertSee('Ambil foto wajah')
            ->assertSee('Unggah foto')
            ->assertDontSee('Unggah file')
            ->assertSee('pb-personal-document-card__top', false)
            ->assertSee('pb-document-status', false)
            ->assertSee('data-auto-submit', false)
            ->assertSee('Dokumen hanya dapat diakses sesuai hak akses pengajuan.')
            ->assertSee('personal-ktp.svg', false)
            ->assertSee('face-guide.png', false)
            ->assertSee('data-face-start', false)
            ->assertSee(route('client.documents.store', [$application->public_id, $faceRequirement->public_id]), false)
            ->assertDontSee('biometr', false);
    }

    public function test_business_workspace_uses_the_refined_layout_without_personal_requirement_leakage(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'code' => 'NPWP_BUSINESS',
            'name' => 'NPWP Badan Usaha',
            'status' => ServiceStatus::ACTIVE,
            'price_amount' => 225000,
            'currency' => 'IDR',
        ]);
        $application = $this->application($user, $service, ApplicationStatus::DRAFT);

        collect([
            ['code' => 'KTP_PENANGGUNG_JAWAB', 'name' => 'KTP penanggung jawab utama', 'is_required' => true],
            ['code' => 'AKTA_NOTARIS', 'name' => 'Akta notaris', 'is_required' => true],
            ['code' => 'SK_AHU', 'name' => 'SK AHU', 'is_required' => true],
            ['code' => 'SURAT_KUASA', 'name' => 'Surat kuasa', 'is_required' => false, 'condition_snapshot' => 'jika diwakilkan'],
        ])->each(fn (array $attributes, int $index) => ApplicationRequirement::factory()->create([
            ...$attributes,
            'application_id' => $application->id,
            'sort_order' => ($index + 1) * 10,
        ]));

        $this->actingAs($user)->get(route('client.applications.show', $application->public_id))
            ->assertOk()
            ->assertSee('pb-workspace--business', false)
            ->assertSee('pb-sidebar-summary', false)
            ->assertSee('Data badan usaha')
            ->assertSee('Pastikan dokumen terlihat jelas dan sesuai persyaratan.')
            ->assertSee('Penanggung jawab utama')
            ->assertSee('Penanggung jawab tambahan')
            ->assertSee('pb-business-document-grid', false)
            ->assertSee('Isi data penanggung jawab utama pengajuan.')
            ->assertDontSee('Anda juga dapat menyimpan secara manual.')
            ->assertSee('KTP penanggung jawab utama')
            ->assertSee('Akta notaris')
            ->assertSee('SK AHU')
            ->assertSee('Surat kuasa')
            ->assertSee('Kondisional')
            ->assertSee('Jika diwakilkan')
            ->assertDontSee('Bagian pengajuan')
            ->assertDontSee('pb-document-row', false)
            ->assertDontSee('Kartu Keluarga')
            ->assertDontSee('Foto Wajah');
    }

    public function test_personal_document_card_shows_revision_context_without_changing_document_rules(): void
    {
        $user = User::factory()->create();
        $application = $this->application($user, $this->service(), ApplicationStatus::REVISION_REQUIRED);
        $requirements = $this->personalRequirements($application);
        $ktp = $requirements->firstWhere('code', 'KTP');
        $ktp->update(['status' => 'REVISION_REQUIRED']);

        Document::factory()->create([
            'application_id' => $application->id,
            'application_requirement_id' => $ktp->id,
            'uploaded_by_user_id' => $user->id,
            'scan_status' => DocumentScanStatus::PASSED,
            'review_status' => DocumentReviewStatus::REVISION_REQUIRED,
            'rejection_reason' => 'Foto identitas tidak terbaca.',
            'revision_instruction' => 'Unggah ulang dokumen yang lebih jelas.',
        ]);

        $this->actingAs($user)->get(route('client.applications.show', $application->public_id))
            ->assertOk()
            ->assertSee('Perlu diperbaiki')
            ->assertSee('Foto identitas tidak terbaca.')
            ->assertSee('Unggah ulang dokumen yang lebih jelas.')
            ->assertSee('Ganti dokumen');
    }

    public function test_result_states_do_not_expose_unverified_results(): void
    {
        $user = User::factory()->create();
        $admin = $this->admin();
        $service = $this->service();

        foreach ([ApplicationStatus::RESULT_UPLOADED, ApplicationStatus::RESULT_REVIEW] as $status) {
            $application = $this->application($user, $service, $status);
            $result = $this->createResult($application, $admin, ResultVerificationStatus::PENDING);

            $response = $this->actingAs($user)->get(route('client.applications.show', $application->public_id));
            $response->assertOk()
                ->assertSee($status === ApplicationStatus::RESULT_UPLOADED ? 'Hasil sedang disiapkan' : 'Hasil sedang diverifikasi')
                ->assertSee('Hasil belum dapat dilihat atau diunduh sampai verifikasi selesai.')
                ->assertDontSee(route('client.results.view', $result->public_id), false)
                ->assertDontSee(route('client.results.download', $result->public_id), false);

            $this->actingAs($user)->get(route('client.results.view', $result->public_id))->assertForbidden();
        }
    }

    public function test_only_verified_result_is_visible_to_its_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $application = $this->application($owner, $this->service(), ApplicationStatus::COMPLETED);
        $result = $this->createResult($application, $this->admin(), ResultVerificationStatus::VERIFIED);

        $this->actingAs($owner)->get(route('client.applications.show', $application->public_id))
            ->assertOk()
            ->assertSee('Dokumen hasil terverifikasi')
            ->assertSee(route('client.results.view', $result->public_id), false)
            ->assertSee(route('client.results.download', $result->public_id), false);

        $this->actingAs($other)->get(route('client.results.view', $result->public_id))->assertForbidden();
    }

    public function test_help_only_lists_owned_application_contexts_for_application_chat(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $service = $this->service();
        $ownedApplication = $this->application($owner, $service, ApplicationStatus::UNDER_REVIEW);
        $foreignApplication = $this->application($other, $service, ApplicationStatus::UNDER_REVIEW);
        $ownedThread = ChatThread::create([
            'application_id' => $ownedApplication->id,
            'client_user_id' => $owner->id,
        ]);
        $foreignThread = ChatThread::create([
            'application_id' => $foreignApplication->id,
            'client_user_id' => $other->id,
        ]);

        $this->actingAs($owner)->get(route('qna'))
            ->assertOk()
            ->assertSee('Pilih pengajuan')
            ->assertSee('Tim kami dapat melihat konteksnya.')
            ->assertSee('Tanya tentang pengajuan ini')
            ->assertSee(route('client.chat.show', $ownedThread->public_id), false)
            ->assertDontSee(route('client.chat.show', $foreignThread->public_id), false);
    }

    private function service(string $code = 'NPWP_PERSONAL', string $name = 'NPWP Perseorangan', int $price = 175000): Service
    {
        $service = Service::factory()->create([
            'code' => $code,
            'name' => $name,
            'status' => ServiceStatus::ACTIVE,
            'price_amount' => $price,
            'currency' => 'IDR',
        ]);
        ServiceRequirement::factory()->create([
            'service_id' => $service->id,
            'code' => $code.'_KTP',
            'name' => 'KTP',
        ]);

        return $service;
    }

    private function application(User $user, Service $service, ApplicationStatus $status): Application
    {
        return Application::factory()->create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => $status,
            'price_amount_snapshot' => $service->price_amount,
            'currency' => $service->currency,
        ]);
    }

    /** @return Collection<int, ApplicationRequirement> */
    private function personalRequirements(Application $application)
    {
        return collect([
            ['code' => 'KTP', 'name' => 'KTP', 'is_required' => true],
            ['code' => 'KK', 'name' => 'Kartu Keluarga', 'is_required' => true],
            ['code' => 'NPWP', 'name' => 'NPWP (Jika ada)', 'is_required' => false],
            ['code' => 'FOTO_WAJAH', 'name' => 'Foto wajah', 'is_required' => true],
        ])->map(fn (array $attributes, int $index) => ApplicationRequirement::factory()->create([
            ...$attributes,
            'application_id' => $application->id,
            'sort_order' => ($index + 1) * 10,
        ]));
    }

    private function admin(): Admin
    {
        $user = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);

        return Admin::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);
    }

    private function createResult(Application $application, Admin $admin, ResultVerificationStatus $status): ResultDocument
    {
        return ResultDocument::create([
            'application_id' => $application->id,
            'type' => ResultDocumentType::PRIMARY_RESULT,
            'original_filename' => 'hasil-sintetis.pdf',
            'stored_filename' => 'hasil-sintetis.pdf',
            'storage_disk' => 'private',
            'storage_path' => 'synthetic/results/hasil-sintetis.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 1024,
            'sha256_checksum' => hash('sha256', 'hasil-sintetis'),
            'scan_status' => DocumentScanStatus::PASSED,
            'verification_status' => $status,
            'uploaded_by_admin_id' => $admin->id,
            'uploaded_at' => now(),
            'verified_by_admin_id' => $status === ResultVerificationStatus::VERIFIED ? $admin->id : null,
            'verified_at' => $status === ResultVerificationStatus::VERIFIED ? now() : null,
        ]);
    }
}
