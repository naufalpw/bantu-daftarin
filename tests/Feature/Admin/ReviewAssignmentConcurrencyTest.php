<?php

namespace Tests\Feature\Admin;

use App\Enums\ApplicationStatus;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\AuditLog;
use App\Models\ChatThread;
use App\Models\Service;
use App\Models\User;
use App\Services\AdminWorkflowService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Concurrency;
use Tests\TestCase;

class ReviewAssignmentConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_concurrent_start_review_assigns_winning_admin_and_actor_consistently(): void
    {
        $fixture = $this->createReviewFixture();
        $appId = $fixture['application']->id;
        $admin1Id = $fixture['admin1']->id;
        $admin2Id = $fixture['admin2']->id;

        $review1 = static function () use ($appId, $admin1Id): string {
            try {
                $workflow = app(AdminWorkflowService::class);
                $app = Application::findOrFail($appId);
                $admin = Admin::findOrFail($admin1Id);
                $workflow->beginReview($app, $admin);

                return 'ADMIN_1_WIN';
            } catch (\DomainException $e) {
                return 'ADMIN_1_REJECTED: '.$e->getMessage();
            }
        };

        $review2 = static function () use ($appId, $admin2Id): string {
            try {
                $workflow = app(AdminWorkflowService::class);
                $app = Application::findOrFail($appId);
                $admin = Admin::findOrFail($admin2Id);
                $workflow->beginReview($app, $admin);

                return 'ADMIN_2_WIN';
            } catch (\DomainException $e) {
                return 'ADMIN_2_REJECTED: '.$e->getMessage();
            }
        };

        $outcomes = Concurrency::driver('process')->run([$review1, $review2]);

        $freshApp = Application::findOrFail($appId);
        $freshChat = ChatThread::where('application_id', $appId)->firstOrFail();
        $history = ApplicationStatusHistory::where('application_id', $appId)
            ->where('to_status', ApplicationStatus::UNDER_REVIEW->value)
            ->firstOrFail();

        $this->assertSame(ApplicationStatus::UNDER_REVIEW, $freshApp->status);

        // Exactly one admin must have won
        $admin1Won = in_array('ADMIN_1_WIN', $outcomes, true);
        $admin2Won = in_array('ADMIN_2_WIN', $outcomes, true);
        $this->assertTrue($admin1Won xor $admin2Won, 'Exactly one admin must win review start');

        $winningAdminId = $admin1Won ? $admin1Id : $admin2Id;

        // Verify that application assignment, chat assignment, and history actor are strictly identical
        $this->assertSame($winningAdminId, $freshApp->assigned_admin_id);
        $this->assertSame($winningAdminId, $freshChat->assigned_admin_id);
        $this->assertSame($winningAdminId, $history->actor_id);
        $this->assertSame('admin', $history->actor_type);

        // Verify audit log consistency
        $audit = AuditLog::where('auditable_type', Application::class)
            ->where('auditable_id', $appId)
            ->where('event', 'application.status_changed')
            ->latest('id')
            ->firstOrFail();
        $this->assertSame($winningAdminId, $audit->actor_id);
        $this->assertSame('admin', $audit->actor_type);
    }

    public function test_same_admin_duplicate_start_review_is_idempotent(): void
    {
        $fixture = $this->createReviewFixture();
        $workflow = app(AdminWorkflowService::class);
        $app = $fixture['application'];
        $admin = $fixture['admin1'];

        $first = $workflow->beginReview($app, $admin);
        $this->assertSame(ApplicationStatus::UNDER_REVIEW, $first->status);
        $this->assertSame($admin->id, $first->assigned_admin_id);

        $second = $workflow->beginReview($first, $admin);
        $this->assertSame(ApplicationStatus::UNDER_REVIEW, $second->status);
        $this->assertSame($admin->id, $second->assigned_admin_id);

        $this->assertSame(1, ApplicationStatusHistory::where('application_id', $app->id)
            ->where('to_status', ApplicationStatus::UNDER_REVIEW->value)
            ->count());
    }

    public function test_stale_application_object_reloads_authoritative_state_and_rejects_invalid_transition(): void
    {
        $fixture = $this->createReviewFixture();
        $workflow = app(AdminWorkflowService::class);
        $staleApp = $fixture['application'];
        $admin = $fixture['admin1'];

        // In database, application transitions to CANCELLED behind the scenes
        Application::where('id', $staleApp->id)->update([
            'status' => ApplicationStatus::CANCELLED->value,
            'assigned_admin_id' => null,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Aplikasi belum siap untuk pemeriksaan.');

        // Calling beginReview with the stale in-memory instance
        $workflow->beginReview($staleApp, $admin);
    }

    public function test_invalid_status_transition_before_lock_is_rejected(): void
    {
        $fixture = $this->createReviewFixture();
        $workflow = app(AdminWorkflowService::class);
        $app = $fixture['application'];
        $admin = $fixture['admin1'];

        $app->update(['status' => ApplicationStatus::AWAITING_PAYMENT->value]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Aplikasi belum siap untuk pemeriksaan.');

        $workflow->beginReview($app, $admin);
    }

    public function test_different_admin_cannot_overwrite_existing_under_review_assignment(): void
    {
        $fixture = $this->createReviewFixture();
        $workflow = app(AdminWorkflowService::class);
        $app = $fixture['application'];
        $admin1 = $fixture['admin1'];
        $admin2 = $fixture['admin2'];

        $workflow->beginReview($app, $admin1);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Aplikasi belum siap untuk pemeriksaan.');

        $workflow->beginReview($app->fresh(), $admin2);
    }

    private function createReviewFixture(): array
    {
        $client = User::factory()->create();
        $adminUser1 = User::factory()->create(['role' => 'SUPER_ADMIN']);
        $admin1 = Admin::create([
            'user_id' => $adminUser1->id,
            'email' => $adminUser1->email,
            'role' => 'SUPER_ADMIN',
            'is_active' => true,
        ]);

        $adminUser2 = User::factory()->create(['role' => 'SUPER_ADMIN']);
        $admin2 = Admin::create([
            'user_id' => $adminUser2->id,
            'email' => $adminUser2->email,
            'role' => 'SUPER_ADMIN',
            'is_active' => true,
        ]);

        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL', 'price_amount' => 100000]);

        $application = Application::create([
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => ApplicationStatus::DOCUMENTS_SUBMITTED,
            'price_amount_snapshot' => 100000,
            'currency' => 'IDR',
        ]);

        $chat = ChatThread::create([
            'application_id' => $application->id,
            'client_user_id' => $client->id,
        ]);

        return [
            'client' => $client,
            'admin1' => $admin1,
            'admin2' => $admin2,
            'application' => $application,
            'chat' => $chat,
        ];
    }
}
