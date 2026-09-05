<?php

namespace Tests\Feature\Chat;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Livewire\ChatThread as ChatThreadComponent;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Service;
use App\Models\User;
use App\Notifications\ChatUnreadNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_thread_participants_can_open_chat(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $thread = $this->threadFor($owner);

        $this->actingAs($owner)
            ->get(route('client.chat.show', $thread->public_id))
            ->assertOk()
            ->assertSee('bd-chat-card')
            ->assertSee('Tim Bantu Daftarin')
            ->assertSee('pb-client-body')
            ->assertDontSee('class="border-b bg-white"', false);
        $this->actingAs($other)->get(route('client.chat.show', $thread->public_id))->assertForbidden();
    }

    public function test_client_can_send_message_and_admin_recipient_is_notified(): void
    {
        Notification::fake();
        $client = User::factory()->create();
        $adminUser = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        $admin = Admin::create(['user_id' => $adminUser->id, 'email' => $adminUser->email, 'role' => UserRole::SUPER_ADMIN, 'is_active' => true]);
        $thread = $this->threadFor($client, $admin);

        Livewire::actingAs($client)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->set('body', '<script>alert(1)</script>')
            ->call('send')
            ->assertSet('body', '');

        $message = ChatMessage::query()->where('chat_thread_id', $thread->id)->latest('id')->firstOrFail();
        $this->assertSame('<script>alert(1)</script>', $message->body);
        $this->assertNotNull($thread->fresh()->last_message_at);
        $this->actingAs($client)->get(route('client.chat.show', $thread->public_id))
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
        Notification::assertSentTo($adminUser, ChatUnreadNotification::class);
        $this->assertDatabaseHas('audit_logs', ['event' => 'chat.message_sent', 'auditable_id' => $message->id]);
    }

    public function test_unread_messages_are_marked_read_by_thread_owner(): void
    {
        $client = User::factory()->create();
        $adminUser = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        $admin = Admin::create(['user_id' => $adminUser->id, 'email' => $adminUser->email, 'role' => UserRole::SUPER_ADMIN, 'is_active' => true]);
        $thread = $this->threadFor($client, $admin);
        $message = $thread->messages()->create(['sender_user_id' => $adminUser->id, 'body' => 'Pesan synthetic']);

        Livewire::actingAs($client)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->call('markRead');

        $this->assertNotNull($message->fresh()->read_at);
        $this->assertSame($client->id, $message->fresh()->read_by_user_id);
    }

    public function test_admin_can_use_assigned_chat_and_client_cannot_read_other_thread_messages(): void
    {
        Notification::fake();
        $client = User::factory()->create();
        $adminUser = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        $admin = Admin::create(['user_id' => $adminUser->id, 'email' => $adminUser->email, 'role' => UserRole::SUPER_ADMIN, 'is_active' => true]);
        $thread = $this->threadFor($client, $admin);

        Livewire::actingAs($adminUser)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->set('body', 'Balasan admin synthetic')
            ->call('send');

        $this->assertDatabaseHas('chat_messages', ['chat_thread_id' => $thread->id, 'sender_user_id' => $adminUser->id, 'body' => 'Balasan admin synthetic']);
        Notification::assertSentTo($client, ChatUnreadNotification::class);
    }

    public function test_typing_indicator_is_ephemeral_scoped_and_hidden_from_sender(): void
    {
        $client = User::factory()->create();
        $adminUser = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        $admin = Admin::create(['user_id' => $adminUser->id, 'email' => $adminUser->email, 'role' => UserRole::SUPER_ADMIN, 'is_active' => true]);
        $thread = $this->threadFor($client, $admin);

        Livewire::actingAs($adminUser)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->set('body', 'Balasan sedang diketik')
            ->call('typing')
            ->assertSet('isOtherParticipantTyping', false);

        Livewire::actingAs($client)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->assertSet('isOtherParticipantTyping', true)
            ->assertSee('Sedang mengetik...');

        Livewire::actingAs($adminUser)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->set('body', '')
            ->call('typing');

        Livewire::actingAs($client)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->call('markRead')
            ->assertSet('isOtherParticipantTyping', false);

        Livewire::actingAs($adminUser)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->set('body', 'Ketik sebentar')
            ->call('typing');

        $this->travel(6)->seconds();
        try {
            Livewire::actingAs($client)
                ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
                ->call('markRead')
                ->assertSet('isOtherParticipantTyping', false);
        } finally {
            $this->travelBack();
        }

        $this->assertDatabaseCount('chat_messages', 0);
    }

    private function threadFor(User $client, ?Admin $admin = null): ChatThread
    {
        $service = Service::factory()->create();
        $application = Application::create([
            'user_id' => $client->id,
            'service_id' => $service->id,
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
}
