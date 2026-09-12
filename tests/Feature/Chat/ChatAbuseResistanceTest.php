<?php

namespace Tests\Feature\Chat;

use App\Enums\ChatThreadType;
use App\Livewire\ChatThread;
use App\Models\AuditLog;
use App\Models\ChatThread as ChatThreadModel;
use App\Models\Notification as NotificationModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class ChatAbuseResistanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clearResolvedInstances();
    }

    public function test_normal_message_sending_below_limit_is_accepted(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $thread = ChatThreadModel::create([
            'client_user_id' => $user->id,
            'context_type' => ChatThreadType::GENERAL_SUPPORT,
            'subject' => 'Bantuan Pengajuan',
        ]);

        $this->actingAs($user);

        Livewire::test(ChatThread::class, ['threadId' => $thread->public_id])
            ->set('body', 'Halo, ada kendala?')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('body', '');

        $this->assertDatabaseHas('chat_messages', [
            'chat_thread_id' => $thread->id,
            'sender_user_id' => $user->id,
            'body' => 'Halo, ada kendala?',
        ]);
    }

    public function test_send_rate_limit_exceeded_rejects_with_user_friendly_error(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $thread = ChatThreadModel::create([
            'client_user_id' => $user->id,
            'context_type' => ChatThreadType::GENERAL_SUPPORT,
            'subject' => 'Bantuan Pengajuan',
        ]);

        $this->actingAs($user);

        $component = Livewire::test(ChatThread::class, ['threadId' => $thread->public_id]);

        // Exhaust the 30 allowed sends
        for ($i = 1; $i <= 30; $i++) {
            $component->set('body', "Pesan nomor {$i}")
                ->call('send')
                ->assertHasNoErrors();
        }

        $this->assertSame(30, $thread->messages()->count());

        // 31st send must be throttled
        $component->set('body', 'Pesan ke-31 yang melampaui batas')
            ->call('send')
            ->assertHasErrors(['body']);

        // Assert no 31st message was created
        $this->assertSame(30, $thread->messages()->count());
    }

    public function test_no_notification_or_audit_created_after_limit_exceeded(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $thread = ChatThreadModel::create([
            'client_user_id' => $user->id,
            'context_type' => ChatThreadType::GENERAL_SUPPORT,
            'subject' => 'Bantuan Pengajuan',
        ]);

        $this->actingAs($user);
        $limiterKey = "chat:send:{$user->id}:{$thread->id}";

        // Simulate rate limiter already exhausted
        for ($i = 0; $i < 30; $i++) {
            RateLimiter::hit($limiterKey, 60);
        }

        $initialAuditCount = AuditLog::query()->where('event', 'chat.message_sent')->count();
        $initialNotificationCount = NotificationModel::query()->count();

        Livewire::test(ChatThread::class, ['threadId' => $thread->public_id])
            ->set('body', 'Pesan saat limit terlampaui')
            ->call('send')
            ->assertHasErrors(['body']);

        $this->assertSame($initialAuditCount, AuditLog::query()->where('event', 'chat.message_sent')->count());
        $this->assertSame($initialNotificationCount, NotificationModel::query()->count());
        $this->assertSame(0, $thread->messages()->count());
    }

    public function test_different_threads_have_independent_send_limits(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $thread1 = ChatThreadModel::create([
            'client_user_id' => $user->id,
            'context_type' => ChatThreadType::GENERAL_SUPPORT,
            'subject' => 'Bantuan 1',
        ]);
        $thread2 = ChatThreadModel::create([
            'client_user_id' => $user->id,
            'context_type' => ChatThreadType::GENERAL_SUPPORT,
            'subject' => 'Bantuan 2',
        ]);

        $this->actingAs($user);
        $key1 = "chat:send:{$user->id}:{$thread1->id}";

        // Exhaust limit on thread 1 only
        for ($i = 0; $i < 30; $i++) {
            RateLimiter::hit($key1, 60);
        }

        // Thread 1 is blocked
        Livewire::test(ChatThread::class, ['threadId' => $thread1->public_id])
            ->set('body', 'Harus ditolak di thread 1')
            ->call('send')
            ->assertHasErrors(['body']);

        // Thread 2 remains usable
        Livewire::test(ChatThread::class, ['threadId' => $thread2->public_id])
            ->set('body', 'Bisa dikirim di thread 2')
            ->call('send')
            ->assertHasNoErrors();

        $this->assertSame(0, $thread1->messages()->count());
        $this->assertSame(1, $thread2->messages()->count());
    }

    public function test_different_users_have_independent_send_limits(): void
    {
        Notification::fake();
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $threadA = ChatThreadModel::create([
            'client_user_id' => $userA->id,
            'context_type' => ChatThreadType::GENERAL_SUPPORT,
            'subject' => 'Bantuan A',
        ]);
        $threadB = ChatThreadModel::create([
            'client_user_id' => $userB->id,
            'context_type' => ChatThreadType::GENERAL_SUPPORT,
            'subject' => 'Bantuan B',
        ]);

        // Exhaust User A's limit
        $keyA = "chat:send:{$userA->id}:{$threadA->id}";
        for ($i = 0; $i < 30; $i++) {
            RateLimiter::hit($keyA, 60);
        }

        $this->actingAs($userA);
        Livewire::test(ChatThread::class, ['threadId' => $threadA->public_id])
            ->set('body', 'User A terblokir')
            ->call('send')
            ->assertHasErrors(['body']);

        $this->actingAs($userB);
        Livewire::test(ChatThread::class, ['threadId' => $threadB->public_id])
            ->set('body', 'User B masih bisa mengirim')
            ->call('send')
            ->assertHasNoErrors();

        $this->assertSame(0, $threadA->messages()->count());
        $this->assertSame(1, $threadB->messages()->count());
    }

    public function test_typing_updates_are_bounded_without_crashing(): void
    {
        $user = User::factory()->create();
        $thread = ChatThreadModel::create([
            'client_user_id' => $user->id,
            'context_type' => ChatThreadType::GENERAL_SUPPORT,
            'subject' => 'Bantuan Pengajuan',
        ]);

        $this->actingAs($user);
        $component = Livewire::test(ChatThread::class, ['threadId' => $thread->public_id]);

        $cacheKey = 'chat-typing:'.$thread->public_id.':'.$user->id;

        // Typing sets the cache
        $component->set('body', 'Sedang mengetik sesuatu')
            ->call('typing');
        $this->assertTrue(Cache::has($cacheKey));

        // Burst typing 100 times should not throw
        for ($i = 0; $i < 100; $i++) {
            $component->call('typing');
        }

        // Clearing body clears the typing cache
        $component->set('body', '')
            ->call('typing');
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_heartbeat_normal_cadence_accepted(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->post(route('presence.heartbeat'));
        $response->assertNoContent();
    }

    public function test_heartbeat_burst_is_throttled(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        // Send 60 requests to hit the limit
        for ($i = 0; $i < 60; $i++) {
            $this->post(route('presence.heartbeat'))->assertNoContent();
        }

        // The 61st request must receive 429 Too Many Requests
        $response = $this->post(route('presence.heartbeat'));
        $response->assertStatus(429);
    }

    public function test_chat_ownership_remains_authoritative(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $thread = ChatThreadModel::create([
            'client_user_id' => $owner->id,
            'context_type' => ChatThreadType::GENERAL_SUPPORT,
            'subject' => 'Bantuan Pribadi',
        ]);

        $this->actingAs($intruder);

        Livewire::test(ChatThread::class, ['threadId' => $thread->public_id])
            ->assertForbidden();
    }
}
