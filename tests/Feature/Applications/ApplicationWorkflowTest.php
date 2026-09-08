<?php

namespace Tests\Feature\Applications;

use App\Enums\ApplicationStatus;
use App\Enums\BusinessRelationship;
use App\Enums\DocumentReviewStatus;
use App\Enums\DocumentScanStatus;
use App\Enums\PaymentStatus;
use App\Enums\ServiceStatus;
use App\Exceptions\InvalidApplicationTransition;
use App\Livewire\ApplicationDetailsForm;
use App\Models\Application;
use App\Models\ApplicationRequirement;
use App\Models\Document;
use App\Models\Service;
use App\Models\ServiceRequirement;
use App\Models\User;
use App\Services\ApplicationTransitionService;
use App\Services\ApplicationWorkflowService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_personal_application_snapshots_service_and_requirement_data(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $service = Service::factory()->create([
            'code' => 'NPWP_PERSONAL',
            'status' => ServiceStatus::ACTIVE,
            'price_amount' => 175000,
        ]);
        $requirement = ServiceRequirement::factory()->create([
            'service_id' => $service->id,
            'code' => 'KTP',
            'name' => 'KTP asli',
            'max_size_bytes' => 5 * 1024 * 1024,
        ]);

        $response = $this->actingAs($user)->post(route('client.applications.store'), [
            'service_public_id' => $service->public_id,
            'kind' => 'NPWP_PERSONAL',
            'consent' => 1,
            'name' => 'Synthetic Personal Client',
            'email' => 'personal@example.test',
            'purpose' => 'Administrasi synthetic',
        ]);

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $response->assertRedirect(route('client.applications.show', $application->public_id));
        $this->assertSame(ApplicationStatus::DRAFT, $application->status);
        $this->assertSame('175000.00', $application->price_amount_snapshot);
        $this->assertSame('Synthetic Personal Client', $application->personalDetails->name);
        $this->assertSame('KTP', $application->requirements->first()->code);
        $this->assertSame($requirement->id, $application->requirements->first()->service_requirement_id);
        $this->assertDatabaseHas('application_consents', ['application_id' => $application->id, 'consent_type' => 'DATA_PROCESSING']);
        $this->assertDatabaseHas('chat_threads', ['application_id' => $application->id, 'client_user_id' => $user->id]);

        $service->forceFill(['price_amount' => 999999])->save();
        $requirement->forceFill(['name' => 'KTP changed later'])->save();
        $this->assertSame('175000.00', $application->fresh()->price_amount_snapshot);
        $this->assertSame('KTP asli', $application->fresh()->requirements->first()->name);
    }

    public function test_business_application_stores_primary_and_optional_representative_relationships(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['code' => 'NPWP_BUSINESS', 'status' => ServiceStatus::ACTIVE]);
        ServiceRequirement::factory()->create(['service_id' => $service->id, 'code' => 'AKTA_NOTARIS']);

        $this->actingAs($user)->post(route('client.applications.store'), [
            'service_public_id' => $service->public_id,
            'kind' => 'NPWP_BUSINESS',
            'consent' => 1,
            'business_name' => 'PT Synthetic Nusantara',
            'business_type' => 'PT',
            'representative' => [
                'name' => 'Synthetic Director',
                'relationship' => BusinessRelationship::DIRECTOR->value,
                'email' => 'director@example.test',
            ],
            'additional_representative' => [
                'name' => 'Synthetic Employee',
                'relationship' => BusinessRelationship::EMPLOYEE->value,
                'email' => 'employee@example.test',
            ],
        ])->assertSessionHasNoErrors();

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('PT Synthetic Nusantara', $application->businessDetails->business_name);
        $this->assertCount(2, $application->representatives);
        $this->assertSame(BusinessRelationship::DIRECTOR, $application->representatives->firstWhere('is_primary', true)->relationship);
        $this->assertSame(BusinessRelationship::EMPLOYEE, $application->representatives->firstWhere('is_primary', false)->relationship);
    }

    public function test_business_application_rejects_partial_additional_representative(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['code' => 'NPWP_BUSINESS', 'status' => ServiceStatus::ACTIVE]);
        ServiceRequirement::factory()->create(['service_id' => $service->id, 'code' => 'AKTA_NOTARIS']);

        $response = $this->actingAs($user)->post(route('client.applications.store'), [
            'service_public_id' => $service->public_id,
            'kind' => 'NPWP_BUSINESS',
            'consent' => 1,
            'business_name' => 'PT Synthetic Partial Representative',
            'representative' => [
                'name' => 'Synthetic Director',
                'relationship' => BusinessRelationship::DIRECTOR->value,
            ],
            'additional_representative' => [
                'name' => 'Synthetic Employee Without Relationship',
            ],
        ]);

        $response->assertSessionHasErrors('additional_representative.relationship');
        $this->assertDatabaseMissing('applications', ['user_id' => $user->id, 'service_id' => $service->id]);

        $emailOnlyResponse = $this->actingAs($user)->post(route('client.applications.store'), [
            'service_public_id' => $service->public_id,
            'kind' => 'NPWP_BUSINESS',
            'consent' => 1,
            'business_name' => 'PT Synthetic Email Only Representative',
            'representative' => [
                'name' => 'Synthetic Director',
                'relationship' => BusinessRelationship::DIRECTOR->value,
            ],
            'additional_representative' => [
                'email' => 'employee@example.test',
            ],
        ]);

        $emailOnlyResponse->assertSessionHasErrors([
            'additional_representative.name',
            'additional_representative.relationship',
        ]);
    }

    public function test_application_transition_records_history_and_audit_and_rejects_arbitrary_target(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL']);
        $application = Application::factory()->create(['user_id' => $user->id, 'service_id' => $service->id, 'status' => ApplicationStatus::DRAFT]);

        app(ApplicationTransitionService::class)->transition($application, ApplicationStatus::AWAITING_DOCUMENTS, $user, 'synthetic submit');
        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $application->id,
            'from_status' => ApplicationStatus::DRAFT->value,
            'to_status' => ApplicationStatus::AWAITING_DOCUMENTS->value,
            'actor_type' => 'user',
            'reason' => 'synthetic submit',
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'application.status_changed', 'auditable_id' => $application->id]);

        $this->expectException(InvalidApplicationTransition::class);
        app(ApplicationTransitionService::class)->transition($application->fresh(), ApplicationStatus::COMPLETED, $user);
    }

    public function test_client_status_field_is_not_an_application_transition_input(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL']);
        ServiceRequirement::factory()->create(['service_id' => $service->id]);
        $application = Application::factory()->create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => ApplicationStatus::DRAFT,
        ]);

        $response = $this->actingAs($user)->put(route('client.applications.update', $application->public_id), [
            'status' => ApplicationStatus::COMPLETED->value,
            'name' => 'Synthetic Updated Name',
        ])->assertSessionHasNoErrors();

        $this->assertSame(ApplicationStatus::DRAFT, $application->fresh()->status);
        $this->assertSame('Synthetic Updated Name', $application->fresh()->personalDetails->name);
    }

    public function test_application_requirement_code_is_unique_per_application(): void
    {
        $application = Application::factory()->create();
        ApplicationRequirement::factory()->create(['application_id' => $application->id, 'code' => 'KTP']);

        $this->expectException(QueryException::class);
        ApplicationRequirement::factory()->create(['application_id' => $application->id, 'code' => 'KTP']);
    }

    public function test_personal_details_livewire_form_autosaves_with_server_validation(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL']);
        $application = Application::factory()->create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => ApplicationStatus::DRAFT,
        ]);

        Livewire::actingAs($user)
            ->test(ApplicationDetailsForm::class, ['application' => $application])
            ->assertSee('Perubahan disimpan otomatis setelah Anda selesai mengisi kolom.')
            ->assertDontSee('Anda juga dapat menyimpan secara manual.')
            ->set('details.name', 'Synthetic Autosave Client')
            ->call('save')
            ->assertSet('saveState', 'Tersimpan otomatis.');

        $this->assertDatabaseHas('personal_application_details', ['application_id' => $application->id, 'name' => 'Synthetic Autosave Client']);
    }

    public function test_personal_detail_selects_normalize_legacy_values_and_validate_allowed_options(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL']);
        $application = Application::factory()->create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => ApplicationStatus::DRAFT,
        ]);
        $application->personalDetails()->create([
            'name' => 'Synthetic Existing Client',
            'gender' => 'Laki-Laki',
            'marital_status' => 'Belum Menikah',
            'family_status' => 'Anak',
        ]);

        Livewire::actingAs($user)
            ->test(ApplicationDetailsForm::class, ['application' => $application])
            ->assertSeeHtml('<select wire:model.blur="details.gender"')
            ->assertSeeHtml('<option value="Pria">Pria</option>')
            ->assertSeeHtml('<option value="Lajang">Lajang</option>')
            ->assertSeeHtml('<option value="Suami">Suami</option>')
            ->assertSet('details.gender', 'Pria')
            ->assertSet('details.marital_status', 'Lajang')
            ->set('details.gender', 'Wanita')
            ->set('details.marital_status', 'Kawin')
            ->set('details.family_status', 'Istri')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saveState', 'Tersimpan otomatis.');

        $this->assertDatabaseHas('personal_application_details', [
            'application_id' => $application->id,
            'gender' => 'Wanita',
            'marital_status' => 'Kawin',
            'family_status' => 'Istri',
        ]);

        Livewire::actingAs($user)
            ->test(ApplicationDetailsForm::class, ['application' => $application->fresh()])
            ->set('details.gender', 'Tidak valid')
            ->call('save')
            ->assertHasErrors(['details.gender' => 'in']);

        $this->actingAs($user)
            ->put(route('client.applications.update', $application->public_id), [
                'name' => 'Synthetic Existing Client',
                'gender' => 'Tidak valid',
            ])
            ->assertSessionHasErrors('gender');
    }

    public function test_personal_detail_selects_accept_each_available_option(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL']);
        $application = Application::factory()->create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => ApplicationStatus::DRAFT,
        ]);

        $component = Livewire::actingAs($user)
            ->test(ApplicationDetailsForm::class, ['application' => $application])
            ->set('details.name', 'Synthetic Select Client');

        foreach ([
            'details.gender' => ['Pria', 'Wanita'],
            'details.marital_status' => ['Lajang', 'Kawin', 'Cerai Hidup', 'Cerai Mati'],
            'details.family_status' => ['Suami', 'Istri', 'Anak'],
        ] as $field => $values) {
            foreach ($values as $value) {
                $component->set($field, $value)->call('save')->assertHasNoErrors();
            }
        }
    }

    public function test_client_submit_requires_documents_and_creates_pending_payment_after_upload(): void
    {
        Notification::fake();
        Storage::fake('private');
        Storage::fake('quarantine');
        $user = User::factory()->create();
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL', 'status' => ServiceStatus::ACTIVE, 'price_amount' => 100000]);
        ServiceRequirement::factory()->create(['service_id' => $service->id, 'code' => 'KTP', 'is_required' => true]);
        ServiceRequirement::factory()->create(['service_id' => $service->id, 'code' => 'KK', 'is_required' => true]);

        $this->actingAs($user)->post(route('client.applications.store'), [
            'service_public_id' => $service->public_id,
            'kind' => 'NPWP_PERSONAL',
            'consent' => 1,
            'name' => 'Synthetic Payment Client',
            'nik' => '3173055501010001',
            'family_card_number' => '3173055501010002',
        ])->assertRedirect();
        $application = Application::query()->where('user_id', $user->id)->latest('id')->firstOrFail();

        $this->actingAs($user)->post(route('client.applications.submit', $application->public_id))->assertRedirect();
        $this->assertSame(ApplicationStatus::AWAITING_DOCUMENTS, $application->fresh()->status);

        $this->actingAs($user)->post(route('client.applications.payment', $application->public_id))
            ->assertSessionHasErrors('error');
        $this->assertSame(ApplicationStatus::AWAITING_DOCUMENTS, $application->fresh()->status);

        $requirement = $application->requirements()->where('code', 'KTP')->firstOrFail();
        $this->actingAs($user)->post(route('client.documents.store', [$application->public_id, $requirement->public_id]), [
            'file' => UploadedFile::fake()->createWithContent('ktp.pdf', "%PDF-1.4\n%%EOF"),
        ])->assertRedirect();

        $this->assertSame(ApplicationStatus::AWAITING_DOCUMENTS, $application->fresh()->status);

        $remainingRequirement = $application->requirements()->where('code', 'KK')->firstOrFail();
        $this->actingAs($user)->post(route('client.documents.store', [$application->public_id, $remainingRequirement->public_id]), [
            'file' => UploadedFile::fake()->createWithContent('kk.pdf', "%PDF-1.4\n%%EOF"),
        ])->assertRedirect()->assertSessionHas('status', 'Dokumen lengkap. Silakan lanjut ke pembayaran.');

        $this->assertSame(ApplicationStatus::DOCUMENTS_READY_FOR_PAYMENT, $application->fresh()->status);
        $this->actingAs($user)->get(route('client.applications.show', $application->public_id))
            ->assertOk()
            ->assertSee('Lanjut ke pembayaran');
        $this->actingAs($user)->post(route('client.applications.payment', $application->public_id))->assertRedirect();
        $this->assertSame(ApplicationStatus::AWAITING_PAYMENT, $application->fresh()->status);
        $this->assertSame(PaymentStatus::PENDING, $application->fresh()->payments()->latest('id')->first()->status);
    }

    public function test_revision_submission_requires_targeted_documents_then_returns_to_review(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL']);
        $application = Application::factory()->create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => ApplicationStatus::REVISION_REQUIRED,
        ]);
        $requirement = ApplicationRequirement::factory()->create([
            'application_id' => $application->id,
            'code' => 'KTP',
            'status' => 'REVISION_REQUIRED',
        ]);
        Document::create([
            'application_id' => $application->id,
            'application_requirement_id' => $requirement->id,
            'version_number' => 1,
            'original_filename' => 'synthetic-rejected.pdf',
            'stored_filename' => 'random-rejected.pdf',
            'storage_disk' => 'private',
            'storage_path' => 'applications/synthetic/rejected.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 1024,
            'sha256_checksum' => hash('sha256', 'synthetic-rejected'),
            'scan_status' => DocumentScanStatus::PASSED,
            'review_status' => DocumentReviewStatus::REVISION_REQUIRED,
            'uploaded_by_user_id' => $user->id,
            'uploaded_at' => now(),
            'active' => true,
        ]);

        try {
            app(ApplicationWorkflowService::class)->submitRevision($application, $user);
            $this->fail('Expected a revision upload requirement exception.');
        } catch (\DomainException $exception) {
            $this->assertSame('Unggah seluruh dokumen yang diminta untuk revisi terlebih dahulu.', $exception->getMessage());
        }

        $requirement->forceFill(['status' => 'PENDING'])->save();
        $submitted = app(ApplicationWorkflowService::class)->submitRevision($application->fresh(), $user);

        $this->assertSame(ApplicationStatus::REVISION_SUBMITTED, $submitted->status);
        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $application->id,
            'from_status' => ApplicationStatus::REVISION_REQUIRED->value,
            'to_status' => ApplicationStatus::REVISION_SUBMITTED->value,
        ]);
    }
}
