<?php

namespace Tests\Feature\Files;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentScanStatus;
use App\Enums\ResultDocumentType;
use App\Enums\ResultVerificationStatus;
use App\Models\Admin;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\ResultDocument;
use App\Models\Service;
use App\Models\User;
use App\Services\AdminWorkflowService;
use App\Services\ExpiredFilePurger;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\UnableToDeleteFile;
use Tests\TestCase;

class FilePurgeCoordinationTest extends TestCase
{
    use RefreshDatabase;

    public function test_purge_claim_wins_before_completion_and_blocks_stale_result_eligibility(): void
    {
        Notification::fake();
        $fixture = $this->resultFixture(ResultVerificationStatus::VERIFIED);
        $claim = app(ExpiredFilePurger::class)->claim(ResultDocument::class, $fixture['result']->id);

        $this->assertNotNull($claim);
        $this->assertNotNull($fixture['result']->fresh()->deletion_scheduled_at);

        try {
            app(AdminWorkflowService::class)->complete($fixture['application'], $fixture['admin']);
            $this->fail('Completion must not accept a result already claimed for retention deletion.');
        } catch (\DomainException $exception) {
            $this->assertSame('Aplikasi belum memiliki hasil utama yang diverifikasi.', $exception->getMessage());
        }

        $this->assertSame(ApplicationStatus::RESULT_REVIEW, $fixture['application']->fresh()->status);
    }

    public function test_purge_claim_wins_before_result_decision_and_blocks_verification_or_rejection(): void
    {
        Notification::fake();
        $fixture = $this->resultFixture(ResultVerificationStatus::PENDING);
        $claim = app(ExpiredFilePurger::class)->claim(ResultDocument::class, $fixture['result']->id);

        $this->assertNotNull($claim);

        foreach ([true, false] as $verified) {
            try {
                app(AdminWorkflowService::class)->verifyResult(
                    $fixture['result'],
                    $fixture['admin'],
                    $verified,
                    $verified ? null : 'synthetic rejection'
                );
                $this->fail('A claimed result must not accept a verification decision.');
            } catch (\DomainException $exception) {
                $this->assertSame('Hasil belum dapat diverifikasi.', $exception->getMessage());
            }
        }

        $this->assertSame(ResultVerificationStatus::PENDING, $fixture['result']->fresh()->verification_status);
    }

    public function test_purge_claim_denies_new_authorized_access_before_physical_delete(): void
    {
        Storage::fake('private');
        $fixture = $this->resultFixture(ResultVerificationStatus::VERIFIED);
        Storage::disk('private')->put($fixture['result']->storage_path, "%PDF-1.4\nsynthetic\n%%EOF");

        $claim = app(ExpiredFilePurger::class)->claim(ResultDocument::class, $fixture['result']->id);

        $this->assertNotNull($claim);
        Storage::disk('private')->assertExists($fixture['result']->storage_path);
        $this->actingAs($fixture['user'])
            ->get(route('client.results.download', $fixture['result']->public_id))
            ->assertForbidden();
    }

    public function test_releasing_a_failed_deletion_claim_restores_authoritative_access_state(): void
    {
        $fixture = $this->resultFixture(ResultVerificationStatus::VERIFIED);
        $purger = app(ExpiredFilePurger::class);
        $claim = $purger->claim(ResultDocument::class, $fixture['result']->id);

        $this->assertNotNull($claim);
        $purger->release($claim);

        $fresh = $fixture['result']->fresh();
        $this->assertNull($fresh->deletion_scheduled_at);
        $this->assertNull($fresh->deleted_at);
    }

    public function test_throwing_delete_releases_only_owned_claim_and_command_continues_with_failure_exit(): void
    {
        $first = $this->resultFixture(ResultVerificationStatus::VERIFIED);
        $second = $this->resultFixture(ResultVerificationStatus::VERIFIED);
        $firstResult = $first['result'];
        $secondResult = $second['result'];
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('delete')->twice()->andReturnUsing(function (string $path) use ($firstResult, $secondResult): bool {
            if ($path === $firstResult->storage_path) {
                $this->assertNotNull($firstResult->fresh()->deletion_scheduled_at);

                throw UnableToDeleteFile::atLocation($path, 'Synthetic adapter failure');
            }

            $this->assertSame($secondResult->storage_path, $path);
            $this->assertNotNull($secondResult->fresh()->deletion_scheduled_at);

            return true;
        });
        Storage::set('private', $disk);

        $this->artisan('files:purge')
            ->expectsOutputToContain('Gagal menghapus')
            ->assertFailed();

        $failed = $firstResult->fresh();
        $this->assertNull($failed->deletion_scheduled_at);
        $this->assertNull($failed->deleted_at);
        $this->assertTrue($failed->retention_until->isPast());
        $this->assertDatabaseMissing('audit_logs', [
            'event' => 'file.purged',
            'auditable_id' => $failed->id,
        ]);

        $purged = $secondResult->fresh();
        $this->assertNotNull($purged->deleted_at);
        $this->assertSame(1, AuditLog::query()->where('event', 'file.purged')->where('auditable_id', $purged->id)->count());
    }

    public function test_obsolete_worker_cannot_release_a_newer_claim(): void
    {
        $fixture = $this->resultFixture(ResultVerificationStatus::VERIFIED);
        $purger = app(ExpiredFilePurger::class);
        $oldClaim = $purger->claim(ResultDocument::class, $fixture['result']->id);
        $this->assertNotNull($oldClaim);

        $fixture['result']->forceFill(['deletion_scheduled_at' => now()->subMinutes(16)])->save();
        $this->travel(1)->second();
        $newClaim = $purger->claim(ResultDocument::class, $fixture['result']->id);
        $this->assertNotNull($newClaim);
        $purger->release($oldClaim);

        $this->assertTrue($fixture['result']->fresh()->deletion_scheduled_at->equalTo($newClaim['claimed_at']));
    }

    public function test_stale_claim_after_physical_delete_can_be_retried_and_finalized(): void
    {
        Storage::fake('private');
        $fixture = $this->resultFixture(ResultVerificationStatus::VERIFIED);
        $fixture['result']->forceFill(['deletion_scheduled_at' => now()->subMinutes(16)])->save();

        $this->assertTrue(app(ExpiredFilePurger::class)->purge(ResultDocument::class, $fixture['result']->id));

        $fresh = $fixture['result']->fresh();
        $this->assertNotNull($fresh->deleted_at);
        $this->assertSame('retention_expired', $fresh->deletion_reason);
        $this->assertSame(1, AuditLog::query()->where('event', 'file.purged')->where('auditable_id', $fresh->id)->count());
    }

    /** @return array{user:User,admin:Admin,application:Application,result:ResultDocument} */
    private function resultFixture(ResultVerificationStatus $verificationStatus): array
    {
        $user = User::factory()->create();
        $adminUser = User::factory()->create(['role' => 'SUPER_ADMIN']);
        $admin = Admin::create([
            'user_id' => $adminUser->id,
            'email' => $adminUser->email,
            'role' => 'SUPER_ADMIN',
            'is_active' => true,
        ]);
        $service = Service::factory()->create(['price_amount' => 100000]);
        $application = Application::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => ApplicationStatus::RESULT_REVIEW,
            'price_amount_snapshot' => 100000,
            'currency' => 'IDR',
        ]);
        $result = ResultDocument::create([
            'application_id' => $application->id,
            'type' => ResultDocumentType::PRIMARY_RESULT,
            'original_filename' => 'synthetic-result.pdf',
            'stored_filename' => (string) Str::uuid().'.pdf',
            'storage_disk' => 'private',
            'storage_path' => 'applications/'.$application->public_id.'/results/synthetic-result.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 64,
            'sha256_checksum' => hash('sha256', 'synthetic result'),
            'scan_status' => DocumentScanStatus::PASSED,
            'verification_status' => $verificationStatus,
            'uploaded_by_admin_id' => $admin->id,
            'uploaded_at' => now()->subDays(100),
            'verified_by_admin_id' => $verificationStatus === ResultVerificationStatus::VERIFIED ? $admin->id : null,
            'verified_at' => $verificationStatus === ResultVerificationStatus::VERIFIED ? now()->subDays(100) : null,
            'retention_until' => now()->subDay(),
        ]);

        return compact('user', 'admin', 'application', 'result');
    }
}
