<?php

namespace Tests\Feature\Files;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentReviewStatus;
use App\Enums\DocumentScanStatus;
use App\Enums\ResultDocumentType;
use App\Enums\ResultVerificationStatus;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ApplicationRequirement;
use App\Models\Document;
use App\Models\ResultDocument;
use App\Models\Service;
use App\Models\ServiceRequirement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageAliasBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private function setupDocument(User $user, string $filename = 'test.pdf'): array
    {
        $service = Service::factory()->create();
        $application = Application::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => ApplicationStatus::DOCUMENTS_SUBMITTED,
            'price_amount_snapshot' => 100000,
            'currency' => 'IDR',
        ]);
        $serviceReq = ServiceRequirement::factory()->create([
            'service_id' => $service->id,
            'code' => 'KTP',
        ]);
        $appReq = ApplicationRequirement::factory()->create([
            'application_id' => $application->id,
            'service_requirement_id' => $serviceReq->id,
            'code' => 'KTP',
            'name' => 'KTP',
            'allowed_extensions' => ['pdf'],
            'allowed_mimes' => ['application/pdf'],
            'max_size_bytes' => 2097152,
        ]);

        $path = "applications/{$application->id}/documents/{$appReq->code}/random-name.pdf";
        Storage::disk('private')->put($path, "%PDF-1.4\ncontent\n%%EOF");

        $document = Document::create([
            'application_id' => $application->id,
            'application_requirement_id' => $appReq->id,
            'storage_disk' => 'private',
            'storage_path' => $path,
            'original_filename' => $filename,
            'stored_filename' => 'random-name.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 30,
            'sha256_checksum' => hash('sha256', "%PDF-1.4\ncontent\n%%EOF"),
            'scan_status' => DocumentScanStatus::PASSED,
            'review_status' => DocumentReviewStatus::PENDING,
            'uploaded_by_user_id' => $user->id,
            'uploaded_at' => now(),
            'version_number' => 1,
            'active' => true,
        ]);

        return compact('service', 'application', 'appReq', 'document', 'path');
    }

    public function test_local_disk_configuration_uncoupled_from_private_and_serve_disabled(): void
    {
        $localConfig = Config::get('filesystems.disks.local');
        $privateConfig = Config::get('filesystems.disks.private');

        $this->assertFalse($localConfig['serve'], 'Generic local disk must have serve = false');
        $this->assertFalse($privateConfig['serve'], 'Private disk must have serve = false');
        $this->assertNotEquals($localConfig['root'], $privateConfig['root'], 'Local disk and private disk must not share the same physical directory root');
        $this->assertStringEndsWith('app/local', str_replace('\\', '/', $localConfig['root']));
        $this->assertStringEndsWith('app/private', str_replace('\\', '/', $privateConfig['root']));
    }

    public function test_private_document_still_streams_through_authorized_route(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $setup = $this->setupDocument($user);

        $response = $this->actingAs($user)->get(route('client.documents.view', $setup['document']->public_id));
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    public function test_cross_owner_document_access_remains_blocked(): void
    {
        Storage::fake('private');
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $setup = $this->setupDocument($owner);

        $response = $this->actingAs($intruder)->get(route('client.documents.view', $setup['document']->public_id));
        $response->assertForbidden();
    }

    public function test_unsigned_direct_framework_storage_url_returns_404(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $setup = $this->setupDocument($user);

        // Attempting to access the file via the framework generic storage route returns 404
        $response = $this->actingAs($user)->get('/storage/'.$setup['path']);
        $response->assertNotFound();
    }

    public function test_path_traversal_on_private_storage_remains_rejected(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $this->setupDocument($user);

        $response = $this->actingAs($user)->get('/storage/../../etc/passwd');
        $response->assertNotFound();
    }

    public function test_authorized_admin_can_access_document_through_admin_route(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $adminUser = User::factory()->create(['role' => 'SUPER_ADMIN', 'is_active' => true]);
        Admin::create(['user_id' => $adminUser->id, 'email' => $adminUser->email, 'role' => 'SUPER_ADMIN', 'is_active' => true]);
        $setup = $this->setupDocument($user);

        $response = $this->actingAs($adminUser)->get(route('admin.documents.view', $setup['document']->public_id));
        $response->assertOk();
    }

    public function test_unverified_result_document_cannot_be_viewed_by_client(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $adminUser = User::factory()->create(['role' => 'SUPER_ADMIN', 'is_active' => true]);
        $admin = Admin::create(['user_id' => $adminUser->id, 'email' => $adminUser->email, 'role' => 'SUPER_ADMIN', 'is_active' => true]);

        $application = Application::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => ApplicationStatus::RESULT_REVIEW,
            'price_amount_snapshot' => 100000,
            'currency' => 'IDR',
        ]);

        $path = "applications/{$application->id}/results/result.pdf";
        Storage::disk('private')->put($path, "%PDF-1.4\nresult\n%%EOF");

        $result = ResultDocument::create([
            'application_id' => $application->id,
            'storage_disk' => 'private',
            'storage_path' => $path,
            'original_filename' => 'result.pdf',
            'stored_filename' => 'result.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 30,
            'sha256_checksum' => hash('sha256', 'test'),
            'scan_status' => DocumentScanStatus::PASSED,
            'verification_status' => ResultVerificationStatus::PENDING,
            'type' => ResultDocumentType::PRIMARY_RESULT,
            'uploaded_by_admin_id' => $admin->id,
            'uploaded_at' => now(),
        ]);

        // Client cannot access pending/unverified result
        $response = $this->actingAs($user)->get(route('client.results.view', $result->public_id));
        $response->assertForbidden();

        // Mark verified
        $result->forceFill([
            'verification_status' => ResultVerificationStatus::VERIFIED,
            'verified_by_admin_id' => $admin->id,
            'verified_at' => now(),
        ])->save();

        $response = $this->actingAs($user)->get(route('client.results.view', $result->public_id));
        $response->assertOk();
    }

    public function test_local_disk_can_be_used_independently_for_non_sensitive_operations(): void
    {
        Storage::fake('local');
        Storage::fake('private');

        Storage::disk('local')->put('temp-reports/report.csv', 'id,name\n1,test');
        Storage::disk('private')->put('applications/sensitive.pdf', 'secret');

        Storage::disk('local')->assertExists('temp-reports/report.csv');
        Storage::disk('private')->assertMissing('temp-reports/report.csv');
        Storage::disk('local')->assertMissing('applications/sensitive.pdf');
    }
}
