<?php

namespace Tests\Feature\Documents;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentReviewAction;
use App\Enums\DocumentReviewStatus;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ApplicationRequirement;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Service;
use App\Models\ServiceRequirement;
use App\Models\User;
use App\Services\DocumentWorkflowService;
use App\Services\ExpiredFilePurger;
use App\Services\PrivateFileReader;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToDeleteFile;
use Tests\TestCase;

class DocumentDestroyHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_client_deletion_claims_deletes_and_finalizes_authoritatively(): void
    {
        Storage::fake('private');
        $fixture = $this->fixture();
        Storage::disk('private')->put($fixture['document']->storage_path, 'synthetic');

        $this->actingAs($fixture['user'])
            ->delete(route('client.documents.destroy', $fixture['document']->public_id))
            ->assertRedirect()
            ->assertSessionHas('status', 'Dokumen dihapus dari versi aktif.');

        Storage::disk('private')->assertMissing($fixture['document']->storage_path);
        $fresh = $fixture['document']->fresh();
        $this->assertFalse($fresh->active);
        $this->assertNotNull($fresh->deleted_at);
        $this->assertSame('client_deleted_before_payment', $fresh->deletion_reason);
        $this->assertSame('PENDING', $fixture['requirement']->fresh()->status);
        $this->assertSame(1, AuditLog::query()->where('event', 'document.deleted')->where('auditable_id', $fresh->id)->count());
    }

    public function test_cross_owner_is_denied_and_already_inactive_is_not_found(): void
    {
        Storage::fake('private');
        $fixture = $this->fixture();
        $other = User::factory()->create();

        $this->actingAs($other)
            ->delete(route('client.documents.destroy', $fixture['document']->public_id))
            ->assertForbidden();

        $fixture['document']->forceFill(['active' => false])->save();
        $this->actingAs($fixture['user'])
            ->delete(route('client.documents.destroy', $fixture['document']->public_id))
            ->assertNotFound();
    }

    public function test_stale_model_cannot_delete_after_application_stage_becomes_ineligible(): void
    {
        Storage::fake('private');
        $fixture = $this->fixture();
        Storage::disk('private')->put($fixture['document']->storage_path, 'synthetic');
        $stale = $fixture['document']->fresh(['application', 'requirement']);
        $fixture['application']->forceFill(['status' => ApplicationStatus::UNDER_REVIEW])->save();

        try {
            app(DocumentWorkflowService::class)->destroy($stale, $fixture['user']);
            $this->fail('A stale client decision must be rejected after the application is locked.');
        } catch (AuthorizationException $exception) {
            $this->assertSame('Dokumen terkunci.', $exception->getMessage());
        }

        Storage::disk('private')->assertExists($fixture['document']->storage_path);
        $this->assertTrue($fixture['document']->fresh()->active);
        $this->assertNull($fixture['document']->fresh()->deletion_scheduled_at);
    }

    public function test_admin_review_winning_first_prevents_client_deletion(): void
    {
        Storage::fake('private');
        $fixture = $this->fixture(ApplicationStatus::UNDER_REVIEW);
        $admin = $this->admin();
        app(DocumentWorkflowService::class)->review($fixture['document'], $admin, DocumentReviewAction::ACCEPT);

        $this->actingAs($fixture['user'])
            ->delete(route('client.documents.destroy', $fixture['document']->public_id))
            ->assertForbidden();

        $this->assertSame(DocumentReviewStatus::LOCKED, $fixture['document']->fresh()->review_status);
        $this->assertTrue($fixture['document']->fresh()->active);
    }

    public function test_client_destroy_winning_first_prevents_later_review_of_deleted_version(): void
    {
        Storage::fake('private');
        $fixture = $this->fixture();
        app(DocumentWorkflowService::class)->destroy($fixture['document'], $fixture['user']);
        $fixture['application']->forceFill(['status' => ApplicationStatus::UNDER_REVIEW])->save();

        try {
            app(DocumentWorkflowService::class)->review($fixture['document']->fresh(), $this->admin(), DocumentReviewAction::ACCEPT);
            $this->fail('A deleted document version must not be reviewable.');
        } catch (\DomainException $exception) {
            $this->assertSame('Dokumen tidak dapat diperiksa.', $exception->getMessage());
        }

        $this->assertFalse($fixture['document']->fresh()->active);
        $this->assertNotNull($fixture['document']->fresh()->deleted_at);
    }

    public function test_throwing_filesystem_delete_releases_owned_claim_without_false_audit(): void
    {
        $fixture = $this->fixture();
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('delete')->once()->andReturnUsing(function (string $path) use ($fixture): never {
            $this->assertSame($fixture['document']->storage_path, $path);
            $this->assertNotNull($fixture['document']->fresh()->deletion_scheduled_at);

            throw UnableToDeleteFile::atLocation($path, 'Synthetic adapter failure');
        });
        Storage::set('private', $disk);

        try {
            app(DocumentWorkflowService::class)->destroy($fixture['document'], $fixture['user']);
            $this->fail('The storage failure must be reported.');
        } catch (\DomainException $exception) {
            $this->assertSame('Dokumen belum dapat dihapus. Silakan coba lagi.', $exception->getMessage());
        }

        $fresh = $fixture['document']->fresh();
        $this->assertTrue($fresh->active);
        $this->assertNull($fresh->deletion_scheduled_at);
        $this->assertNull($fresh->deleted_at);
        $this->assertDatabaseMissing('audit_logs', ['event' => 'document.deleted', 'auditable_id' => $fresh->id]);
    }

    public function test_purge_claim_and_destroy_claim_cannot_both_own_the_document(): void
    {
        Storage::fake('private');
        $fixture = $this->fixture();
        $fixture['document']->forceFill(['retention_until' => now()->subMinute()])->save();
        $claim = app(ExpiredFilePurger::class)->claim(Document::class, $fixture['document']->id);
        $this->assertNotNull($claim);

        try {
            app(DocumentWorkflowService::class)->destroy($fixture['document'], $fixture['user']);
            $this->fail('The active purge claim must block client destruction.');
        } catch (AuthorizationException) {
        }

        $this->assertTrue($fixture['document']->fresh()->deletion_scheduled_at->equalTo($claim['claimed_at']));
        $this->assertTrue(app(ExpiredFilePurger::class)->purge(Document::class, $fixture['document']->id) === false);
    }

    public function test_private_access_admitted_first_keeps_its_open_stream_while_destroy_finalizes(): void
    {
        Storage::fake('private');
        $fixture = $this->fixture();
        Storage::disk('private')->put($fixture['document']->storage_path, 'synthetic-open-stream');
        [, $stream] = app(PrivateFileReader::class)->openDocument($fixture['document']->public_id, $fixture['user']);

        app(DocumentWorkflowService::class)->destroy($fixture['document'], $fixture['user']);

        $this->assertSame('synthetic-open-stream', stream_get_contents($stream));
        fclose($stream);
        $this->assertNotNull($fixture['document']->fresh()->deleted_at);

        $this->expectException(AuthorizationException::class);
        app(PrivateFileReader::class)->openDocument($fixture['document']->public_id, $fixture['user']);
    }

    /** @return array{user:User,application:Application,requirement:ApplicationRequirement,document:Document} */
    private function fixture(ApplicationStatus $status = ApplicationStatus::AWAITING_PAYMENT): array
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL']);
        $serviceRequirement = ServiceRequirement::factory()->create(['service_id' => $service->id, 'code' => 'KTP']);
        $application = Application::factory()->create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => $status,
        ]);
        $requirement = ApplicationRequirement::factory()->create([
            'application_id' => $application->id,
            'service_requirement_id' => $serviceRequirement->id,
            'code' => 'KTP',
            'status' => 'PENDING',
        ]);
        $document = Document::factory()->create([
            'application_id' => $application->id,
            'application_requirement_id' => $requirement->id,
            'uploaded_by_user_id' => $user->id,
            'storage_disk' => 'private',
            'storage_path' => 'applications/'.$application->public_id.'/documents/synthetic.pdf',
            'active' => true,
            'review_status' => DocumentReviewStatus::PENDING,
        ]);

        return compact('user', 'application', 'requirement', 'document');
    }

    private function admin(): Admin
    {
        $user = User::factory()->create(['role' => 'SUPER_ADMIN']);

        return Admin::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => 'SUPER_ADMIN',
            'is_active' => true,
        ]);
    }
}
