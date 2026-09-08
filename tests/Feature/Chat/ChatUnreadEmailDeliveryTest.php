<?php

namespace Tests\Feature\Chat;

use App\Enums\ApplicationStatus;
use App\Enums\ChatThreadType;
use App\Enums\UserRole;
use App\Jobs\SendChatUnreadEmail;
use App\Livewire\ChatThread as ChatThreadComponent;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ChatThread;
use App\Models\ChatThreadUserState;
use App\Models\Service;
use App\Models\User;
use App\Notifications\ChatUnreadNotification;
use App\Services\ChatUnreadEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class ChatUnreadEmailDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_rapid_messages_in_one_recipient_thread_create_one_delayed_opportunity_and_one_notification(): void
    {
        Queue::fake();
        Notification::fake();
        [$client, $adminUser, $admin] = $this->participants();
        $thread = $this->applicationThreadFor($client, $admin);

        $this->send($client, $thread, 'Pesan pertama.');
        $this->send($client, $thread, 'Pesan kedua.');
        $this->send($client, $thread, 'Pesan ketiga.');

        $job = $this->onlyQueuedJob();
        $this->assertSame($thread->id, $job->threadId);
        $this->assertSame($adminUser->id, $job->recipientId);
        $this->assertTrue($job->afterCommit);
        $this->assertNotNull($job->delay);

        $state = $this->deliveryState($thread, $adminUser);
        $this->assertSame($job->pendingToken, $state->chat_email_pending_token);
        $delaySeconds = $state->chat_email_pending_at->diffInSeconds($job->delay);
        $this->assertGreaterThanOrEqual(ChatUnreadEmailService::COALESCING_DELAY_SECONDS, $delaySeconds);
        $this->assertLessThanOrEqual(ChatUnreadEmailService::COALESCING_DELAY_SECONDS + 1, $delaySeconds);

        $job->handle(app(ChatUnreadEmailService::class));
        $job->handle(app(ChatUnreadEmailService::class));

        $this->assertCount(1, Notification::sent($adminUser, ChatUnreadNotification::class));
        $state->refresh();
        $this->assertNull($state->chat_email_pending_token);
        $this->assertNotNull($state->chat_email_last_sent_at);
    }

    public function test_recipient_reading_before_execution_suppresses_the_pending_email(): void
    {
        Queue::fake();
        Notification::fake();
        [$client, $adminUser, $admin] = $this->participants();
        $thread = $this->applicationThreadFor($client, $admin);

        $this->send($client, $thread, 'Mohon tinjau dokumen saya.');
        $job = $this->onlyQueuedJob();

        Livewire::actingAs($adminUser)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id]);

        $job->handle(app(ChatUnreadEmailService::class));

        Notification::assertNothingSent();
        $state = $this->deliveryState($thread, $adminUser);
        $this->assertNull($state->chat_email_pending_token);
        $this->assertNull($state->chat_email_last_sent_at);
    }

    public function test_newer_unread_message_still_sends_after_an_older_message_is_read_before_execution(): void
    {
        Queue::fake();
        Notification::fake();
        [$client, $adminUser, $admin] = $this->participants();
        $thread = $this->applicationThreadFor($client, $admin);

        $this->send($client, $thread, 'Pesan lama.');
        $job = $this->onlyQueuedJob();

        Livewire::actingAs($adminUser)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id]);
        $this->send($client, $thread, 'Pesan terbaru yang belum dibaca.');

        Queue::assertPushed(SendChatUnreadEmail::class, 1);
        $job->handle(app(ChatUnreadEmailService::class));

        Notification::assertSentTo($adminUser, ChatUnreadNotification::class);
    }

    public function test_recipient_and_thread_pairs_remain_independent(): void
    {
        Queue::fake();
        [$client, $adminUser, $admin] = $this->participants();
        $firstThread = $this->applicationThreadFor($client, $admin);
        $secondThread = $this->applicationThreadFor($client, $admin);

        $this->send($client, $firstThread, 'Pesan untuk pengajuan pertama.');
        $this->send($client, $secondThread, 'Pesan untuk pengajuan kedua.');

        Queue::assertPushed(SendChatUnreadEmail::class, 2);
        $jobs = Queue::pushed(SendChatUnreadEmail::class);
        $this->assertEqualsCanonicalizing([$firstThread->id, $secondThread->id], $jobs->pluck('threadId')->all());
        $this->assertEqualsCanonicalizing([$adminUser->id, $adminUser->id], $jobs->pluck('recipientId')->all());
    }

    public function test_a_later_unread_burst_creates_a_new_notification_opportunity(): void
    {
        Queue::fake();
        Notification::fake();
        [$client, $adminUser, $admin] = $this->participants();
        $thread = $this->applicationThreadFor($client, $admin);

        $this->send($client, $thread, 'Burst pertama.');
        $firstJob = $this->onlyQueuedJob();
        $firstJob->handle(app(ChatUnreadEmailService::class));

        $this->send($client, $thread, 'Burst berikutnya.');
        Queue::assertPushed(SendChatUnreadEmail::class, 2);
        $secondJob = Queue::pushed(SendChatUnreadEmail::class)->last();
        $secondJob->handle(app(ChatUnreadEmailService::class));

        $this->assertCount(2, Notification::sent($adminUser, ChatUnreadNotification::class));
    }

    public function test_different_recipients_remain_independent(): void
    {
        Queue::fake();
        [, $adminUser, $admin] = $this->participants();
        $firstClient = User::factory()->create();
        $secondClient = User::factory()->create();
        $firstThread = $this->applicationThreadFor($firstClient, $admin);
        $secondThread = $this->applicationThreadFor($secondClient, $admin);

        $this->send($adminUser, $firstThread, 'Pesan untuk client pertama.');
        $this->send($adminUser, $secondThread, 'Pesan untuk client kedua.');

        Queue::assertPushed(SendChatUnreadEmail::class, 2);
        $jobs = Queue::pushed(SendChatUnreadEmail::class);
        $this->assertEqualsCanonicalizing([$firstClient->id, $secondClient->id], $jobs->pluck('recipientId')->all());
    }

    public function test_application_and_general_support_use_the_correct_recipient_in_both_directions(): void
    {
        Queue::fake();
        [$client, $adminUser, $admin] = $this->participants();
        $applicationThread = $this->applicationThreadFor($client, $admin);
        $generalThread = $this->generalThreadFor($client, $admin);

        $this->send($client, $applicationThread, 'Pesan aplikasi dari client.');
        $this->send($adminUser, $applicationThread, 'Pesan aplikasi dari admin.');
        $this->send($client, $generalThread, 'Pesan umum dari client.');
        $this->send($adminUser, $generalThread, 'Pesan umum dari admin.');

        Queue::assertPushed(SendChatUnreadEmail::class, 4);
        $jobs = Queue::pushed(SendChatUnreadEmail::class);
        $this->assertTrue($jobs->contains(fn (SendChatUnreadEmail $job): bool => $job->threadId === $applicationThread->id && $job->recipientId === $adminUser->id));
        $this->assertTrue($jobs->contains(fn (SendChatUnreadEmail $job): bool => $job->threadId === $applicationThread->id && $job->recipientId === $client->id));
        $this->assertTrue($jobs->contains(fn (SendChatUnreadEmail $job): bool => $job->threadId === $generalThread->id && $job->recipientId === $adminUser->id));
        $this->assertTrue($jobs->contains(fn (SendChatUnreadEmail $job): bool => $job->threadId === $generalThread->id && $job->recipientId === $client->id));
    }

    public function test_chat_email_actions_open_the_exact_authorized_route_without_message_content(): void
    {
        [$client, $adminUser, $admin] = $this->participants();
        $applicationThread = $this->applicationThreadFor($client, $admin);
        $generalThread = $this->generalThreadFor($client, $admin);

        foreach ([$applicationThread, $generalThread] as $thread) {
            $notification = new ChatUnreadNotification($thread->public_id);
            $this->assertSame(route('admin.chat.show', $thread->public_id), $notification->toMail($adminUser)->actionUrl);
            $this->assertSame(route('client.chat.show', $thread->public_id), $notification->toMail($client)->actionUrl);
            $this->assertSame('Buka percakapan', $notification->toMail($client)->actionText);
            $this->assertNotContains('Isi pesan rahasia', $notification->toMail($client)->introLines);
        }

        $otherClient = User::factory()->create();
        $this->actingAs($otherClient)
            ->get(route('client.chat.show', $generalThread->public_id))
            ->assertForbidden();
    }

    public function test_unassigned_application_thread_does_not_fan_out_to_admins(): void
    {
        Queue::fake();
        [$client] = $this->participants();
        $thread = $this->applicationThreadFor($client);

        $this->send($client, $thread, 'Pesan tanpa admin ter-assign.');

        Queue::assertNothingPushed();
    }

    public function test_own_or_deleted_messages_do_not_satisfy_the_unread_email_condition(): void
    {
        Notification::fake();
        [$client] = $this->participants();
        $thread = $this->generalThreadFor($client);
        $token = '0e1e6000-0000-4000-8000-000000000001';
        ChatThreadUserState::create([
            'chat_thread_id' => $thread->id,
            'user_id' => $client->id,
            'chat_email_pending_token' => $token,
            'chat_email_pending_at' => now(),
        ]);
        $thread->messages()->create(['sender_user_id' => $client->id, 'body' => 'Pesan saya sendiri.']);
        $thread->messages()->create([
            'sender_user_id' => User::factory()->create()->id,
            'body' => 'Pesan yang sudah dihapus.',
            'deleted_at' => now(),
        ]);

        app(ChatUnreadEmailService::class)->deliver($thread->id, $client->id, $token);

        Notification::assertNothingSent();
    }

    public function test_rolled_back_message_scheduling_does_not_dispatch_a_chat_email_job(): void
    {
        config()->set('queue.default', 'database');
        [$client, $adminUser, $admin] = $this->participants();
        $thread = $this->applicationThreadFor($client, $admin);

        try {
            DB::transaction(function () use ($thread, $adminUser): void {
                $lockedThread = ChatThread::query()->lockForUpdate()->findOrFail($thread->id);
                app(ChatUnreadEmailService::class)->schedule($lockedThread, $adminUser);

                throw new RuntimeException('Rollback expected.');
            });
        } catch (RuntimeException) {
            // The queued opportunity must not escape the rolled-back transaction.
        }

        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseMissing('chat_thread_user_states', [
            'chat_thread_id' => $thread->id,
            'user_id' => $adminUser->id,
        ]);
    }

    private function onlyQueuedJob(): SendChatUnreadEmail
    {
        Queue::assertPushed(SendChatUnreadEmail::class, 1);

        return Queue::pushed(SendChatUnreadEmail::class)->sole();
    }

    private function deliveryState(ChatThread $thread, User $recipient): ChatThreadUserState
    {
        return ChatThreadUserState::query()
            ->where('chat_thread_id', $thread->id)
            ->where('user_id', $recipient->id)
            ->firstOrFail();
    }

    private function send(User $sender, ChatThread $thread, string $body): void
    {
        Livewire::actingAs($sender)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->set('body', $body)
            ->call('send');
    }

    /** @return array{User, User, Admin} */
    private function participants(): array
    {
        $client = User::factory()->create();
        $adminUser = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        $admin = Admin::create([
            'user_id' => $adminUser->id,
            'email' => $adminUser->email,
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        return [$client, $adminUser, $admin];
    }

    private function applicationThreadFor(User $client, ?Admin $admin = null): ChatThread
    {
        $application = Application::create([
            'user_id' => $client->id,
            'service_id' => Service::factory()->create()->id,
            'status' => ApplicationStatus::DRAFT,
            'price_amount_snapshot' => 100000,
            'currency' => 'IDR',
        ]);

        return ChatThread::create([
            'application_id' => $application->id,
            'client_user_id' => $client->id,
            'assigned_admin_id' => $admin?->id,
        ]);
    }

    private function generalThreadFor(User $client, ?Admin $admin = null): ChatThread
    {
        return ChatThread::create([
            'client_user_id' => $client->id,
            'assigned_admin_id' => $admin?->id,
            'context_type' => ChatThreadType::GENERAL_SUPPORT,
        ]);
    }
}
