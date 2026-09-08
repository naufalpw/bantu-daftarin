<?php

namespace Tests\Feature\Chat;

use App\Enums\ApplicationStatus;
use App\Enums\ChatThreadType;
use App\Enums\UserRole;
use App\Livewire\ChatThread as ChatThreadComponent;
use App\Livewire\GlobalChatNotifier;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Service;
use App\Models\User;
use App\Services\ChatMessageManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GlobalChatNotifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_notifier_uses_new_message_ids_and_does_not_toast_historical_unread_messages(): void
    {
        [$client, $adminUser, $thread] = $this->applicationThread();
        $this->message($thread, $adminUser, 'Pesan lama belum dibaca.');

        $notifier = Livewire::actingAs($client)
            ->test(GlobalChatNotifier::class)
            ->assertSet('unreadThreadCount', 1)
            ->assertDontSee('Pesan lama belum dibaca.');

        $newMessage = $this->message($thread, $adminUser, 'Mohon periksa kelengkapan dokumen Anda.');

        $notifier->call('poll')
            ->assertSee('Tim Bantu Daftarin')
            ->assertSee('Mohon periksa kelengkapan dokumen Anda.')
            ->assertSee('Buka percakapan')
            ->assertDontSee('Buka chat')
            ->assertSee(route('client.chat.show', $thread->public_id), false);

        $this->assertSame(1, $notifier->get('toasts')[$thread->public_id]['count']);

        $notifier->call('poll');

        $this->assertSame(1, $notifier->get('toasts')[$thread->public_id]['count']);
        $this->assertNull($newMessage->fresh()->read_at);
    }

    public function test_new_messages_in_one_thread_are_coalesced_and_dismissal_does_not_mark_them_read(): void
    {
        [$client, $adminUser, $thread] = $this->applicationThread();
        $notifier = Livewire::actingAs($client)->test(GlobalChatNotifier::class);
        $first = $this->message($thread, $adminUser, 'Pesan pertama.');
        $this->message($thread, $adminUser, 'Pesan kedua.');

        $notifier->call('poll');

        $this->assertCount(1, $notifier->get('toasts'));
        $this->assertSame(2, $notifier->get('toasts')[$thread->public_id]['count']);

        $notifier->call('dismissToast', $thread->public_id)
            ->assertSet('toasts', []);

        $this->assertNull($first->fresh()->read_at);
    }

    public function test_notifier_reconciles_an_edited_or_deleted_unread_message_without_marking_it_read(): void
    {
        [$client, $adminUser, $thread] = $this->applicationThread();
        $notifier = Livewire::actingAs($client)->test(GlobalChatNotifier::class);
        $message = $this->message($thread, $adminUser, 'Pesan awal untuk notifier.');

        $notifier->call('poll')->assertSee('Pesan awal untuk notifier.');

        app(ChatMessageManagementService::class)->edit($thread, $message->id, $adminUser, 'Pesan yang telah diperbarui.');
        $notifier->call('poll')->assertSee('Pesan yang telah diperbarui.');

        app(ChatMessageManagementService::class)->delete($thread, $message->id, $adminUser);
        $notifier->call('poll')->assertSet('toasts', [])->assertSet('unreadThreadCount', 0);
        $this->assertNull($message->fresh()->read_at);
    }

    public function test_notifier_suppresses_the_active_thread_but_alerts_for_a_different_thread(): void
    {
        [$client, $adminUser, $activeThread] = $this->applicationThread();
        $otherThread = $this->applicationThreadFor($client);
        $notifier = Livewire::actingAs($client)
            ->test(GlobalChatNotifier::class, ['activeThreadId' => $activeThread->public_id]);

        $this->message($activeThread, $adminUser, 'Pesan pada percakapan aktif.');
        $this->message($otherThread, $adminUser, 'Pesan pada percakapan lain.');

        $notifier->call('poll')
            ->assertSee('Pesan pada percakapan lain.')
            ->assertDontSee('Pesan pada percakapan aktif.')
            ->assertSee(route('client.chat.show', $otherThread->public_id), false);

        $this->assertArrayNotHasKey($activeThread->public_id, $notifier->get('toasts'));
    }

    public function test_client_notifier_keeps_general_support_context_and_ignores_own_messages(): void
    {
        [$client, $adminUser] = $this->users();
        $thread = ChatThread::create([
            'client_user_id' => $client->id,
            'context_type' => ChatThreadType::GENERAL_SUPPORT,
        ]);
        $notifier = Livewire::actingAs($client)->test(GlobalChatNotifier::class);

        $this->message($thread, $client, 'Pesan milik klien sendiri.');
        $notifier->call('poll')->assertSet('unreadThreadCount', 0)->assertDontSee('Pesan milik klien sendiri.');

        $this->message($thread, $adminUser, 'Silakan jelaskan kendala Anda.');
        $notifier->call('poll')
            ->assertSee('Bantuan Umum')
            ->assertSee('Silakan jelaskan kendala Anda.')
            ->assertDontSee('ID …');
    }

    public function test_admin_notifier_uses_client_and_safe_application_context(): void
    {
        [$client, $adminUser, $thread] = $this->applicationThread();
        $notifier = Livewire::actingAs($adminUser)->test(GlobalChatNotifier::class);

        $this->message($thread, $client, 'Saya ingin menanyakan dokumen.');

        $notifier->call('poll')
            ->assertSee('Pesan baru dari '.$client->name)
            ->assertSee($thread->application->service->name)
            ->assertSee(route('admin.chat.show', $thread->public_id), false);

        $this->assertSame(1, $notifier->get('unreadThreadCount'));
    }

    public function test_notifier_badge_clears_after_the_existing_thread_read_behavior_runs(): void
    {
        [$client, $adminUser, $thread] = $this->applicationThread();
        $notifier = Livewire::actingAs($client)->test(GlobalChatNotifier::class);
        $this->message($thread, $adminUser, 'Pesan yang perlu dibaca.');

        $notifier->call('poll')->assertSet('unreadThreadCount', 1);

        Livewire::actingAs($client)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->call('markRead');

        $notifier->call('poll')->assertSet('unreadThreadCount', 0);
    }

    public function test_sidebar_badge_counts_unread_threads_and_refreshes_after_a_thread_is_read(): void
    {
        [$client, $adminUser, $firstThread] = $this->applicationThread();
        $secondThread = $this->applicationThreadFor($client);
        $notifier = Livewire::actingAs($client)->test(GlobalChatNotifier::class);

        foreach (range(1, 4) as $number) {
            $this->message($firstThread, $adminUser, 'Pesan pertama '.$number.'.');
        }
        foreach (range(1, 2) as $number) {
            $this->message($secondThread, $adminUser, 'Pesan kedua '.$number.'.');
        }

        $notifier->call('poll')->assertSet('unreadThreadCount', 2);

        Livewire::actingAs($client)
            ->test(ChatThreadComponent::class, ['threadId' => $firstThread->public_id]);

        $notifier->dispatch('chat-unread-updated')->assertSet('unreadThreadCount', 1);

        Livewire::actingAs($client)
            ->test(ChatThreadComponent::class, ['threadId' => $secondThread->public_id]);

        $notifier->dispatch('chat-unread-updated')
            ->assertSet('unreadThreadCount', 0)
            ->assertDontSee('bd-chat-unread-badge', false);
    }

    public function test_client_cannot_receive_another_clients_chat_notification(): void
    {
        [$client, $adminUser] = $this->users();
        $otherClient = User::factory()->create();
        $otherThread = $this->applicationThreadFor($otherClient);
        $notifier = Livewire::actingAs($client)->test(GlobalChatNotifier::class);

        $this->message($otherThread, $adminUser, 'Pesan untuk klien lain.');

        $notifier->call('poll')
            ->assertSet('unreadThreadCount', 0)
            ->assertDontSee('Pesan untuk klien lain.');
    }

    public function test_notifier_is_mounted_only_in_authenticated_client_and_admin_layouts(): void
    {
        [$client, $adminUser] = $this->users();

        $this->actingAs($client)
            ->get(route('client.dashboard'))
            ->assertOk()
            ->assertSee('bd-chat-notifier', false)
            ->assertSee('wire:poll.12s', false);

        $this->actingAs($adminUser)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('bd-chat-notifier', false)
            ->assertSee('wire:poll.12s', false);

        $this->get(route('home'))->assertDontSee('bd-chat-notifier', false);
    }

    public function test_empty_notifier_does_not_render_an_empty_alpine_teleport_template(): void
    {
        [$client] = $this->users();

        Livewire::actingAs($client)
            ->test(GlobalChatNotifier::class)
            ->assertSet('toasts', [])
            ->assertDontSee('x-teleport="body"', false);
    }

    /**
     * @return array{User, User, ChatThread}
     */
    private function applicationThread(): array
    {
        [$client, $adminUser, $admin] = $this->usersWithAdmin();

        return [$client, $adminUser, $this->applicationThreadFor($client, $admin)];
    }

    private function applicationThreadFor(User $client, ?Admin $admin = null): ChatThread
    {
        $service = Service::factory()->create(['name' => 'NPWP Perseorangan']);
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

    private function message(ChatThread $thread, User $sender, string $body): ChatMessage
    {
        return $thread->messages()->create(['sender_user_id' => $sender->id, 'body' => $body]);
    }

    /**
     * @return array{User, User}
     */
    private function users(): array
    {
        [, $adminUser] = $this->usersWithAdmin();

        return [User::factory()->create(), $adminUser];
    }

    /**
     * @return array{User, User, Admin}
     */
    private function usersWithAdmin(): array
    {
        $client = User::factory()->create(['name' => 'Klien Sintetis']);
        $adminUser = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        $admin = Admin::create([
            'user_id' => $adminUser->id,
            'email' => $adminUser->email,
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        return [$client, $adminUser, $admin];
    }
}
