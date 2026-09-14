<?php

namespace Tests\Feature\Documents;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentReviewAction;
use App\Enums\DocumentReviewStatus;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ApplicationRequirement;
use App\Models\Document;
use App\Models\Service;
use App\Models\ServiceRequirement;
use App\Models\User;
use App\Services\DocumentWorkflowService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentLockOrderConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_overlapping_upload_and_review_use_parent_first_locks_without_deadlock(): void
    {
        $fixture = $this->fixture();
        $barrierDirectory = storage_path('framework/testing/document-lock-race-'.Str::uuid());
        File::ensureDirectoryExists($barrierDirectory);

        $applicationId = $fixture['application']->id;
        $requirementId = $fixture['requirement']->id;
        $documentId = $fixture['document']->id;
        $userId = $fixture['user']->id;
        $adminId = $fixture['admin']->id;

        $upload = static function () use ($barrierDirectory, $applicationId, $requirementId, $userId): string {
            $waitFor = static function (string $path): void {
                $deadline = microtime(true) + 10;
                while (! is_file($path)) {
                    if (microtime(true) >= $deadline) {
                        throw new \RuntimeException('Document concurrency barrier timed out.');
                    }
                    usleep(10_000);
                }
            };

            return DB::transaction(function () use ($barrierDirectory, $applicationId, $requirementId, $userId, $waitFor): string {
                Application::query()->lockForUpdate()->findOrFail($applicationId);
                touch($barrierDirectory.DIRECTORY_SEPARATOR.'application.locked');
                $waitFor($barrierDirectory.DIRECTORY_SEPARATOR.'review.started');

                $document = app(DocumentWorkflowService::class)->upload(
                    Application::findOrFail($applicationId),
                    ApplicationRequirement::findOrFail($requirementId),
                    UploadedFile::fake()->createWithContent('replacement.pdf', "%PDF-1.4\nsynthetic replacement\n%%EOF"),
                    User::findOrFail($userId),
                );

                return 'UPLOADED:'.$document->id;
            });
        };

        $review = static function () use ($barrierDirectory, $documentId, $adminId): string {
            $deadline = microtime(true) + 10;
            $lockMarker = $barrierDirectory.DIRECTORY_SEPARATOR.'application.locked';
            while (! is_file($lockMarker)) {
                if (microtime(true) >= $deadline) {
                    throw new \RuntimeException('Document concurrency barrier timed out.');
                }
                usleep(10_000);
            }

            touch($barrierDirectory.DIRECTORY_SEPARATOR.'review.started');

            try {
                app(DocumentWorkflowService::class)->review(
                    Document::findOrFail($documentId),
                    Admin::findOrFail($adminId),
                    DocumentReviewAction::ACCEPT,
                );

                return 'REVIEWED';
            } catch (\DomainException $exception) {
                return 'REVIEW_REJECTED:'.$exception->getMessage();
            }
        };

        try {
            $outcomes = Concurrency::driver('process')->run([$upload, $review]);
        } finally {
            File::deleteDirectory($barrierDirectory);
        }

        $this->assertStringStartsWith('UPLOADED:', $outcomes[0]);
        $this->assertSame('REVIEW_REJECTED:Dokumen tidak dapat diperiksa.', $outcomes[1]);
        $this->assertSame(1, Document::query()->where('application_id', $applicationId)->where('active', true)->count());
        $this->assertFalse($fixture['document']->fresh()->active);
        $this->assertSame(DocumentReviewStatus::PENDING, $fixture['document']->fresh()->review_status);
        $this->assertSame('PENDING', $fixture['requirement']->fresh()->status);

        $active = Document::query()->where('application_id', $applicationId)->where('active', true)->firstOrFail();
        $this->assertSame(2, $active->version_number);
        $this->assertSame(DocumentReviewStatus::PENDING, $active->review_status);

        Storage::disk('private')->delete($active->storage_path);
    }

    public function test_overlapping_client_destroy_and_upload_serialize_to_one_active_new_version(): void
    {
        $fixture = $this->fixture();
        $barrierDirectory = storage_path('framework/testing/document-destroy-upload-race-'.Str::uuid());
        File::ensureDirectoryExists($barrierDirectory);
        $applicationId = $fixture['application']->id;
        $requirementId = $fixture['requirement']->id;
        $documentId = $fixture['document']->id;
        $userId = $fixture['user']->id;

        $destroy = static function () use ($barrierDirectory, $applicationId, $documentId, $userId): string {
            return DB::transaction(function () use ($barrierDirectory, $applicationId, $documentId, $userId): string {
                Application::query()->lockForUpdate()->findOrFail($applicationId);
                touch($barrierDirectory.DIRECTORY_SEPARATOR.'destroy.locked');
                $deadline = microtime(true) + 10;
                while (! is_file($barrierDirectory.DIRECTORY_SEPARATOR.'upload.started')) {
                    if (microtime(true) >= $deadline) {
                        throw new \RuntimeException('Document destroy/upload barrier timed out.');
                    }
                    usleep(10_000);
                }

                app(DocumentWorkflowService::class)->destroy(
                    Document::findOrFail($documentId),
                    User::findOrFail($userId),
                );

                return 'DESTROYED';
            });
        };

        $upload = static function () use ($barrierDirectory, $applicationId, $requirementId, $userId): string {
            $deadline = microtime(true) + 10;
            while (! is_file($barrierDirectory.DIRECTORY_SEPARATOR.'destroy.locked')) {
                if (microtime(true) >= $deadline) {
                    throw new \RuntimeException('Document destroy/upload barrier timed out.');
                }
                usleep(10_000);
            }
            touch($barrierDirectory.DIRECTORY_SEPARATOR.'upload.started');

            $document = app(DocumentWorkflowService::class)->upload(
                Application::findOrFail($applicationId),
                ApplicationRequirement::findOrFail($requirementId),
                UploadedFile::fake()->createWithContent('replacement-after-delete.pdf', "%PDF-1.4\nsynthetic replacement\n%%EOF"),
                User::findOrFail($userId),
            );

            return 'UPLOADED:'.$document->id;
        };

        try {
            $outcomes = Concurrency::driver('process')->run([$destroy, $upload]);
        } finally {
            File::deleteDirectory($barrierDirectory);
        }

        $this->assertSame('DESTROYED', $outcomes[0]);
        $this->assertStringStartsWith('UPLOADED:', $outcomes[1]);
        $this->assertFalse($fixture['document']->fresh()->active);
        $this->assertNotNull($fixture['document']->fresh()->deleted_at);
        $this->assertSame(1, Document::query()->where('application_id', $applicationId)->where('active', true)->count());
        $active = Document::query()->where('application_id', $applicationId)->where('active', true)->sole();
        $this->assertSame(2, $active->version_number);
        $this->assertSame(DocumentReviewStatus::PENDING, $active->review_status);
        $this->assertSame('PENDING', $fixture['requirement']->fresh()->status);

        Storage::disk('private')->delete($active->storage_path);
    }

    /** @return array{user:User,admin:Admin,application:Application,requirement:ApplicationRequirement,document:Document} */
    private function fixture(): array
    {
        $user = User::factory()->create();
        $adminUser = User::factory()->create(['role' => 'SUPER_ADMIN']);
        $admin = Admin::create([
            'user_id' => $adminUser->id,
            'email' => $adminUser->email,
            'role' => 'SUPER_ADMIN',
            'is_active' => true,
        ]);
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL']);
        $serviceRequirement = ServiceRequirement::factory()->create([
            'service_id' => $service->id,
            'code' => 'KTP',
            'allowed_extensions' => ['pdf'],
            'allowed_mimes' => ['application/pdf'],
            'max_size_bytes' => 5 * 1024 * 1024,
        ]);
        $application = Application::factory()->create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => ApplicationStatus::REVISION_REQUIRED,
        ]);
        $requirement = ApplicationRequirement::factory()->create([
            'application_id' => $application->id,
            'service_requirement_id' => $serviceRequirement->id,
            'code' => 'KTP',
            'allowed_extensions' => ['pdf'],
            'allowed_mimes' => ['application/pdf'],
            'max_size_bytes' => 5 * 1024 * 1024,
            'status' => 'REVISION_REQUIRED',
            'active' => true,
        ]);
        $document = Document::factory()->create([
            'application_id' => $application->id,
            'application_requirement_id' => $requirement->id,
            'uploaded_by_user_id' => $user->id,
            'version_number' => 1,
            'active' => true,
            'scan_status' => 'PASSED',
            'review_status' => DocumentReviewStatus::PENDING,
        ]);

        return compact('user', 'admin', 'application', 'requirement', 'document');
    }
}
