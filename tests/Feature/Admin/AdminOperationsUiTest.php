<?php

namespace Tests\Feature\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\ChatThreadType;
use App\Enums\DocumentReviewStatus;
use App\Enums\DocumentScanStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ApplicationRequirement;
use App\Models\ApplicationStatusHistory;
use App\Models\BusinessRepresentative;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Document;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOperationsUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_and_filter_operational_applications(): void
    {
        $admin = $this->admin();
        $client = User::factory()->create(['name' => 'Klien Review']);
        $review = $this->application($client, ApplicationStatus::DOCUMENTS_SUBMITTED, 'NPWP Review');
        $completed = $this->application($client, ApplicationStatus::COMPLETED, 'NPWP Selesai');

        $this->actingAs($admin->user)
            ->get(route('admin.applications.index', ['filter' => 'review', 'q' => 'Klien Review']))
            ->assertOk()
            ->assertSee('Daftar pengajuan')
            ->assertSee('Perlu ditinjau')
            ->assertSee('…'.strtoupper(substr($review->public_id, -6)))
            ->assertDontSee('…'.strtoupper(substr($completed->public_id, -6)));
    }

    public function test_document_queue_is_contextual_and_links_to_application_review(): void
    {
        $admin = $this->admin();
        $client = User::factory()->create(['name' => 'Klien Dokumen']);
        $application = $this->application($client, ApplicationStatus::UNDER_REVIEW);
        $firstRequirement = ApplicationRequirement::create([
            'application_id' => $application->id,
            'code' => 'KTP',
            'name' => 'KTP',
            'is_required' => true,
            'allowed_extensions' => ['jpg'],
            'allowed_mimes' => ['image/jpeg'],
            'max_size_bytes' => 1024,
            'status' => 'PENDING',
        ]);
        $secondRequirement = ApplicationRequirement::create([
            'application_id' => $application->id,
            'code' => 'KK',
            'name' => 'Kartu Keluarga',
            'is_required' => true,
            'allowed_extensions' => ['jpg'],
            'allowed_mimes' => ['image/jpeg'],
            'max_size_bytes' => 1024,
            'status' => 'PENDING',
        ]);
        Document::factory()->create([
            'application_id' => $application->id,
            'application_requirement_id' => $firstRequirement->id,
            'review_status' => DocumentReviewStatus::PENDING,
            'scan_status' => DocumentScanStatus::PASSED,
            'uploaded_by_user_id' => $client->id,
        ]);
        Document::factory()->create([
            'application_id' => $application->id,
            'application_requirement_id' => $secondRequirement->id,
            'review_status' => DocumentReviewStatus::PENDING,
            'scan_status' => DocumentScanStatus::PASSED,
            'uploaded_by_user_id' => $client->id,
        ]);

        $response = $this->actingAs($admin->user)
            ->get(route('admin.documents.index', ['filter' => 'review']));

        $response
            ->assertOk()
            ->assertSee('Antrian review pengajuan')
            ->assertSee($application->service->name)
            ->assertSee('2 dari 2 dokumen tersedia')
            ->assertSee(route('admin.applications.show', $application->public_id).'#documents-title', false)
            ->assertDontSee('File Manager');

        $this->assertSame(1, substr_count($response->getContent(), route('admin.applications.show', $application->public_id).'#documents-title'));
    }

    public function test_application_detail_keeps_payment_read_only_and_document_review_contextual(): void
    {
        $admin = $this->admin();
        $client = User::factory()->create(['name' => 'Klien Detail']);
        $application = $this->application($client, ApplicationStatus::UNDER_REVIEW);
        $requirement = ApplicationRequirement::create([
            'application_id' => $application->id,
            'code' => 'KK',
            'name' => 'Kartu Keluarga',
            'is_required' => true,
            'allowed_extensions' => ['pdf'],
            'allowed_mimes' => ['application/pdf'],
            'max_size_bytes' => 1024,
            'status' => 'PENDING',
        ]);
        $document = Document::factory()->create([
            'application_id' => $application->id,
            'application_requirement_id' => $requirement->id,
            'uploaded_by_user_id' => $client->id,
        ]);
        Payment::create(['application_id' => $application->id, 'provider' => 'fake', 'reference_id' => 'ADMIN-DETAIL-PAYMENT', 'amount' => 150000, 'currency' => 'IDR', 'payment_method' => PaymentMethod::BRI, 'status' => PaymentStatus::PAID, 'paid_at' => now()]);

        $this->actingAs($admin->user)
            ->get(route('admin.applications.show', $application->public_id))
            ->assertOk()
            ->assertSee('Ringkasan operasional')
            ->assertSee('Pembayaran')
            ->assertSee('Kartu Keluarga')
            ->assertSee('PDF')
            ->assertSee(route('admin.documents.view', $document->public_id), false)
            ->assertSee(route('admin.documents.review', $document->public_id), false)
            ->assertSee('Catat keputusan review')
            ->assertDontSee('Setujui pembayaran')
            ->assertDontSee('Nomor NIK');
    }

    public function test_personal_application_data_is_grouped_and_sensitive_values_are_masked(): void
    {
        $admin = $this->admin();
        $client = User::factory()->create(['name' => 'Klien Data Aman']);
        $application = $this->application($client, ApplicationStatus::DRAFT);
        $application->personalDetails()->create([
            'name' => 'Klien Data Aman',
            'email' => 'data.aman@example.test',
            'gender' => 'Pria',
            'marital_status' => 'Kawin',
            'family_status' => 'Suami',
            'purpose' => 'Pembuatan NPWP',
            'nik' => '3174010101011234',
            'family_card_number' => '3174010101015678',
        ]);

        $this->actingAs($admin->user)
            ->get(route('admin.applications.show', $application->public_id))
            ->assertOk()
            ->assertSee('Jenis kelamin')
            ->assertSee('Status pernikahan')
            ->assertSee('Status dalam keluarga')
            ->assertSee('Pembuatan NPWP')
            ->assertSee('************1234')
            ->assertSee('************5678')
            ->assertDontSee('3174010101011234')
            ->assertDontSee('3174010101015678');
    }

    public function test_business_application_data_uses_business_and_representative_sections(): void
    {
        $admin = $this->admin();
        $client = User::factory()->create(['name' => 'Klien Badan']);
        $application = $this->application($client, ApplicationStatus::DRAFT, 'NPWP Badan Usaha');
        $application->businessDetails()->create([
            'business_name' => 'PT Contoh Nusantara',
            'business_type' => 'PT',
            'purpose' => 'Pembuatan NPWP Badan',
        ]);
        BusinessRepresentative::create([
            'application_id' => $application->id,
            'name' => 'Direktur Contoh',
            'relationship' => 'DIRECTOR',
            'email' => 'direktur@example.test',
            'is_primary' => true,
        ]);

        $this->actingAs($admin->user)
            ->get(route('admin.applications.show', $application->public_id))
            ->assertOk()
            ->assertSee('Informasi badan')
            ->assertSee('Penanggung jawab')
            ->assertSee('PT Contoh Nusantara')
            ->assertSee('Perseroan Terbatas (PT)')
            ->assertSee('Direktur Contoh')
            ->assertSee('direktur@example.test');
    }

    public function test_support_inbox_distinguishes_general_and_application_contexts(): void
    {
        $admin = $this->admin();
        $client = User::factory()->create(['name' => 'Klien Dukungan']);
        $application = $this->application($client, ApplicationStatus::UNDER_REVIEW);
        $applicationThread = ChatThread::create(['application_id' => $application->id, 'client_user_id' => $client->id, 'context_type' => ChatThreadType::APPLICATION]);
        $generalThread = ChatThread::create(['client_user_id' => $client->id, 'context_type' => ChatThreadType::GENERAL_SUPPORT]);
        ChatMessage::create(['chat_thread_id' => $applicationThread->id, 'sender_user_id' => $client->id, 'body' => 'Mohon tinjau dokumen.']);

        $this->actingAs($admin->user)
            ->get(route('admin.support.index'))
            ->assertOk()
            ->assertSee('Dukungan Pengajuan')
            ->assertSee('Bantuan Umum')
            ->assertSee('Buka')
            ->assertSee('is-unread', false)
            ->assertSee(route('admin.chat.show', $generalThread->public_id), false);

        $this->actingAs($admin->user)
            ->get(route('admin.support.index', ['filter' => 'unread', 'q' => 'Klien Dukungan']))
            ->assertOk()
            ->assertSee('Mohon tinjau dokumen.')
            ->assertDontSee('Pertanyaan tanpa konteks pengajuan.');

        $this->actingAs($admin->user)
            ->get(route('admin.support.index', ['q' => 'tidak-ada-thread']))
            ->assertOk()
            ->assertSee('Percakapan tidak ditemukan');
    }

    public function test_admin_chat_uses_contextual_headers_without_fake_presence(): void
    {
        $admin = $this->admin();
        $client = User::factory()->create(['name' => 'Klien Percakapan']);
        $application = $this->application($client, ApplicationStatus::UNDER_REVIEW);
        $applicationThread = ChatThread::create(['application_id' => $application->id, 'client_user_id' => $client->id, 'context_type' => ChatThreadType::APPLICATION]);
        $generalThread = ChatThread::create(['client_user_id' => $client->id, 'context_type' => ChatThreadType::GENERAL_SUPPORT]);
        ChatMessage::create(['chat_thread_id' => $applicationThread->id, 'sender_user_id' => $client->id, 'body' => 'Pesan dari klien.']);
        ChatMessage::create(['chat_thread_id' => $applicationThread->id, 'sender_user_id' => $admin->user->id, 'body' => 'Pesan dari admin.', 'read_at' => now(), 'read_by_user_id' => $client->id]);

        $this->actingAs($admin->user)
            ->get(route('admin.chat.show', $applicationThread->public_id))
            ->assertOk()
            ->assertSee('DUKUNGAN PENGAJUAN')
            ->assertSee($application->service->name)
            ->assertSee('Buka pengajuan')
            ->assertSee(route('admin.applications.show', $application->public_id), false)
            ->assertSee('bd-chat-message--incoming', false)
            ->assertSee('bd-chat-message--outgoing', false)
            ->assertSee('Dibaca')
            ->assertDontSee('Online');

        $this->actingAs($admin->user)
            ->get(route('admin.chat.show', $generalThread->public_id))
            ->assertOk()
            ->assertSee('BANTUAN UMUM')
            ->assertSee('Percakapan tanpa konteks pengajuan.')
            ->assertSee('Belum ada percakapan')
            ->assertDontSee('Buka pengajuan');
    }

    public function test_admin_activity_is_curated_and_does_not_render_raw_audit_values(): void
    {
        $admin = $this->admin();
        $client = User::factory()->create(['name' => 'Klien Aktivitas']);
        $application = $this->application($client, ApplicationStatus::UNDER_REVIEW);
        ApplicationStatusHistory::create(['application_id' => $application->id, 'to_status' => ApplicationStatus::UNDER_REVIEW, 'actor_type' => 'admin', 'reason' => 'Review dimulai.', 'created_at' => now()]);
        Payment::create(['application_id' => $application->id, 'provider' => 'fake', 'reference_id' => 'ADMIN-ACTIVITY-PAYMENT', 'amount' => 150000, 'currency' => 'IDR', 'payment_method' => PaymentMethod::BCA, 'status' => PaymentStatus::PAID, 'paid_at' => now()]);

        $this->actingAs($admin->user)
            ->get(route('admin.activity.index', ['category' => 'document']))
            ->assertOk()
            ->assertSee('Aktivitas operasional')
            ->assertSee('Pemeriksaan dokumen dimulai')
            ->assertDontSee('user_agent')
            ->assertDontSee('provider_payload');
    }

    public function test_admin_user_directory_is_read_only_and_excludes_admins(): void
    {
        $admin = $this->admin();
        $client = User::factory()->create(['name' => 'Pelanggan Direktori']);
        $application = $this->application($client, ApplicationStatus::DRAFT);

        $this->actingAs($admin->user)
            ->get(route('admin.users.index', ['q' => 'Pelanggan']))
            ->assertOk()
            ->assertSee('Direktori pelanggan')
            ->assertSee($client->email)
            ->assertDontSee($admin->user->email)
            ->assertDontSee('Hapus')
            ->assertDontSee('Edit');

        $this->actingAs($admin->user)
            ->get(route('admin.users.show', $client->public_id))
            ->assertOk()
            ->assertSee($application->service->name)
            ->assertSee(route('admin.applications.show', $application->public_id), false);
    }

    public function test_client_cannot_open_new_admin_operational_routes(): void
    {
        $client = User::factory()->create();

        $this->actingAs($client)->get(route('admin.documents.index'))->assertForbidden();
        $this->actingAs($client)->get(route('admin.activity.index'))->assertForbidden();
        $this->actingAs($client)->get(route('admin.users.index'))->assertForbidden();
    }

    private function admin(): Admin
    {
        $user = User::factory()->create(['role' => UserRole::SUPER_ADMIN, 'name' => 'Admin Operasional']);

        return Admin::create(['user_id' => $user->id, 'email' => $user->email, 'role' => UserRole::SUPER_ADMIN, 'is_active' => true]);
    }

    private function application(User $user, ApplicationStatus $status, string $serviceName = 'NPWP Perseorangan'): Application
    {
        $service = Service::factory()->create(['name' => $serviceName]);

        return Application::factory()->create(['user_id' => $user->id, 'service_id' => $service->id, 'status' => $status]);
    }
}
