<?php

namespace Tests\Feature\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\ResultDocumentType;
use App\Enums\ResultVerificationStatus;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\AuditLog;
use App\Models\ResultDocument;
use App\Models\Service;
use App\Models\User;
use App\Services\AdminWorkflowService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResultCompletionConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_concurrent_verify_and_complete_maintains_verified_result_invariant(): void
    {
        $setup = $this->createResultReviewFixture(ResultVerificationStatus::PENDING);
        $appId = $setup['application']->id;
        $resultId = $setup['result']->id;
        $adminId = $setup['admin']->id;

        $verify = static function () use ($resultId, $adminId): string {
            try {
                $workflow = app(AdminWorkflowService::class);
                $result = ResultDocument::findOrFail($resultId);
                $admin = Admin::findOrFail($adminId);
                $workflow->verifyResult($result, $admin, true);

                return 'VERIFY_SUCCESS';
            } catch (\Throwable $e) {
                return 'VERIFY_ERROR: '.$e->getMessage();
            }
        };

        $complete = static function () use ($appId, $adminId): string {
            try {
                $workflow = app(AdminWorkflowService::class);
                $app = Application::findOrFail($appId);
                $admin = Admin::findOrFail($adminId);
                $workflow->complete($app, $admin);

                return 'COMPLETE_SUCCESS';
            } catch (\DomainException $e) {
                return 'COMPLETE_REJECTED: '.$e->getMessage();
            } catch (\Throwable $e) {
                return 'COMPLETE_ERROR: '.$e->getMessage();
            }
        };

        $results = Concurrency::driver('process')->run([$verify, $complete]);

        $freshApp = Application::findOrFail($appId);
        $freshResult = ResultDocument::findOrFail($resultId);

        // Core invariant: If COMPLETED, primary result MUST be VERIFIED
        if ($freshApp->status === ApplicationStatus::COMPLETED) {
            $this->assertSame(ResultVerificationStatus::VERIFIED, $freshResult->verification_status);
            $this->assertTrue($freshApp->hasVerifiedPrimaryResult());
        } else {
            $this->assertSame(ApplicationStatus::RESULT_REVIEW, $freshApp->status);
        }

        // Verify that no unhandled exceptions occurred
        foreach ($results as $res) {
            $this->assertStringStartsNotWith('VERIFY_ERROR', $res);
            $this->assertStringStartsNotWith('COMPLETE_ERROR', $res);
        }
    }

    public function test_concurrent_reject_and_complete_prevents_completed_without_verified_result(): void
    {
        $setup = $this->createResultReviewFixture(ResultVerificationStatus::VERIFIED);
        $appId = $setup['application']->id;
        $resultId = $setup['result']->id;
        $adminId = $setup['admin']->id;

        $reject = static function () use ($resultId, $adminId): string {
            try {
                $workflow = app(AdminWorkflowService::class);
                $result = ResultDocument::findOrFail($resultId);
                $admin = Admin::findOrFail($adminId);
                $workflow->verifyResult($result, $admin, false, 'Nomor NPWP tidak sesuai');

                return 'REJECT_SUCCESS';
            } catch (\DomainException $e) {
                return 'REJECT_BLOCKED: '.$e->getMessage();
            } catch (\Throwable $e) {
                return 'REJECT_ERROR: '.$e->getMessage();
            }
        };

        $complete = static function () use ($appId, $adminId): string {
            try {
                $workflow = app(AdminWorkflowService::class);
                $app = Application::findOrFail($appId);
                $admin = Admin::findOrFail($adminId);
                $workflow->complete($app, $admin);

                return 'COMPLETE_SUCCESS';
            } catch (\DomainException $e) {
                return 'COMPLETE_BLOCKED: '.$e->getMessage();
            } catch (\Throwable $e) {
                return 'COMPLETE_ERROR: '.$e->getMessage();
            }
        };

        $results = Concurrency::driver('process')->run([$reject, $complete]);

        $freshApp = Application::findOrFail($appId);
        $freshResult = ResultDocument::findOrFail($resultId);

        // Invariant: A COMPLETED application must NEVER have a REJECTED result!
        if ($freshApp->status === ApplicationStatus::COMPLETED) {
            $this->assertSame(ResultVerificationStatus::VERIFIED, $freshResult->verification_status);
        } else {
            $this->assertSame(ResultVerificationStatus::REJECTED, $freshResult->verification_status);
            $this->assertSame(ApplicationStatus::RESULT_REVIEW, $freshApp->status);
        }

        // Neither process should crash with an unhandled exception
        foreach ($results as $res) {
            $this->assertStringStartsNotWith('REJECT_ERROR', $res);
            $this->assertStringStartsNotWith('COMPLETE_ERROR', $res);
        }
    }

    public function test_concurrent_verify_and_reject_serializes_without_deadlock(): void
    {
        $setup = $this->createResultReviewFixture(ResultVerificationStatus::PENDING);
        $resultId = $setup['result']->id;
        $adminId = $setup['admin']->id;

        $verify = static function () use ($resultId, $adminId): string {
            $workflow = app(AdminWorkflowService::class);
            $result = ResultDocument::findOrFail($resultId);
            $admin = Admin::findOrFail($adminId);
            $workflow->verifyResult($result, $admin, true);

            return 'VERIFIED';
        };

        $reject = static function () use ($resultId, $adminId): string {
            $workflow = app(AdminWorkflowService::class);
            $result = ResultDocument::findOrFail($resultId);
            $admin = Admin::findOrFail($adminId);
            $workflow->verifyResult($result, $admin, false, 'Alasan ditolak');

            return 'REJECTED';
        };

        $outcomes = Concurrency::driver('process')->run([$verify, $reject]);

        $freshResult = ResultDocument::findOrFail($resultId);
        $this->assertContains($freshResult->verification_status, [
            ResultVerificationStatus::VERIFIED,
            ResultVerificationStatus::REJECTED,
        ]);

        // Audit log should capture both verification changes
        $this->assertSame(2, AuditLog::where('event', 'result.verification_changed')->where('auditable_id', $resultId)->count());
    }

    public function test_two_concurrent_completion_requests_produce_one_history_and_no_error(): void
    {
        $setup = $this->createResultReviewFixture(ResultVerificationStatus::VERIFIED);
        $appId = $setup['application']->id;
        $adminId = $setup['admin']->id;

        $complete = static function () use ($appId, $adminId): string {
            $workflow = app(AdminWorkflowService::class);
            $app = Application::findOrFail($appId);
            $admin = Admin::findOrFail($adminId);
            $workflow->complete($app, $admin);

            return 'OK';
        };

        $results = Concurrency::driver('process')->run([$complete, $complete]);

        $this->assertSame(['OK', 'OK'], array_values($results));
        $freshApp = Application::findOrFail($appId);
        $this->assertSame(ApplicationStatus::COMPLETED, $freshApp->status);

        // Exactly one transition history to COMPLETED
        $completedHistories = ApplicationStatusHistory::where('application_id', $appId)
            ->where('to_status', ApplicationStatus::COMPLETED->value)
            ->count();
        $this->assertSame(1, $completedHistories);
    }

    public function test_already_completed_application_is_idempotent(): void
    {
        $setup = $this->createResultReviewFixture(ResultVerificationStatus::VERIFIED);
        $workflow = app(AdminWorkflowService::class);
        $app = $setup['application'];
        $admin = $setup['admin'];

        $first = $workflow->complete($app, $admin);
        $this->assertSame(ApplicationStatus::COMPLETED, $first->status);

        $second = $workflow->complete($first, $admin);
        $this->assertSame(ApplicationStatus::COMPLETED, $second->status);

        $this->assertSame(1, ApplicationStatusHistory::where('application_id', $app->id)->where('to_status', ApplicationStatus::COMPLETED->value)->count());
    }

    private function createResultReviewFixture(ResultVerificationStatus $verificationStatus): array
    {
        $user = User::factory()->create();
        $adminUser = User::factory()->create(['role' => 'SUPER_ADMIN']);
        $admin = Admin::create([
            'user_id' => $adminUser->id,
            'email' => $adminUser->email,
            'role' => 'SUPER_ADMIN',
            'is_active' => true,
        ]);
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL', 'price_amount' => 100000]);

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
            'original_filename' => 'surat-keterangan-npwp.pdf',
            'stored_filename' => (string) Str::uuid().'.pdf',
            'storage_disk' => 'private',
            'storage_path' => 'applications/'.$application->public_id.'/results/dummy.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 1024,
            'sha256_checksum' => hash('sha256', 'dummy content'),
            'scan_status' => 'PASSED',
            'verification_status' => $verificationStatus,
            'uploaded_by_admin_id' => $admin->id,
            'uploaded_at' => now(),
            'verified_by_admin_id' => $verificationStatus === ResultVerificationStatus::VERIFIED ? $admin->id : null,
            'verified_at' => $verificationStatus === ResultVerificationStatus::VERIFIED ? now() : null,
        ]);

        return [
            'user' => $user,
            'admin' => $admin,
            'application' => $application,
            'result' => $result,
        ];
    }
}
