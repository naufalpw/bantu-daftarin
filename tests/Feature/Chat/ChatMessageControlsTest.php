<?php

namespace Tests\Feature\Chat;

use App\Enums\ApplicationStatus;
use App\Enums\ChatThreadType;
use App\Enums\UserRole;
use App\Livewire\ChatThread as ChatThreadComponent;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\ChatThreadUserState;
use App\Models\Service;
use App\Models\User;
use App\Services\ChatMessageManagementService;
use App\Services\ChatThreadArchiveService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChatMessageControlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sender_can_edit_an_unread_message_within_ten_minutes(): void
    {
        [$client, $adminUser, $thread] = $this->thread();
        $message = $this->message($thread, $client, 'Dokumen sudah saya upload.');

        Livewire::actingAs($client)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->call('startEditing', $message->id)
            ->set('editingBody', 'Dokumen KTP sudah saya upload.')
            ->assertSee('bd-chat-bubble--editing', false)
            ->assertSee('Edit pesan')
            ->assertSee('Batal')
            ->assertSee('Simpan')
            ->call('saveEdit')
            ->assertSet('editingMessageId', null)
            ->assertSee('Dokumen KTP sudah saya upload.')
            ->assertSee('Diedit');

        $message->refresh();
        $this->assertSame('Dokumen KTP sudah saya upload.', $message->body);
        $this->assertNotNull($message->edited_at);
        $this->assertNull($message->read_at);
        $this->assertDatabaseHas('audit_logs', ['event' => 'chat.message_edited', 'auditable_id' => $message->id]);
        $this->assertSame($adminUser->id, $thread->assignedAdmin->user_id);
    }

    public function test_message_edit_requires_sender_unread_window_and_non_deleted_state(): void
    {
        [$client, $adminUser, $thread] = $this->thread();
        $service = app(ChatMessageManagementService::class);
        $message = $this->message($thread, $client, 'Pesan awal.');

        $this->expectException(AuthorizationException::class);
        $service->edit($thread, $message->id, $adminUser, 'Tidak boleh.');
    }

    public function test_read_expired_deleted_and_blank_messages_cannot_be_edited(): void
    {
        [$client, $adminUser, $thread] = $this->thread();
        $service = app(ChatMessageManagementService::class);

        $read = $this->message($thread, $client, 'Sudah dibaca.', ['read_at' => now(), 'read_by_user_id' => $adminUser->id]);
        $expired = $this->message($thread, $client, 'Sudah kedaluwarsa.', ['created_at' => now()->subMinutes(11), 'updated_at' => now()->subMinutes(11)]);
        $deleted = $this->message($thread, $client, 'Sudah dihapus.', ['deleted_at' => now(), 'deleted_by_user_id' => $client->id]);

        foreach ([$read, $expired, $deleted] as $message) {
            try {
                $service->edit($thread, $message->id, $client, 'Tidak boleh berubah.');
                $this->fail('Pesan yang tidak lagi eligible tidak boleh diedit.');
            } catch (DomainException) {
                $this->assertTrue(true);
            }
        }

        Livewire::actingAs($client)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->call('startEditing', $this->message($thread, $client, 'Pesan kosong.')->id)
            ->set('editingBody', '   ')
            ->call('saveEdit')
            ->assertHasErrors(['editingBody' => 'required']);
    }

    public function test_sender_can_tombstone_unread_message_without_hard_deleting_it(): void
    {
        [$client, $adminUser, $thread] = $this->thread();
        $message = $this->message($thread, $client, 'Pesan yang akan ditarik.');

        Livewire::actingAs($client)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->call('confirmDelete', $message->id)
            ->call('deleteMessage')
            ->assertSee('Pesan ini telah dihapus')
            ->assertDontSee('Pesan yang akan ditarik.');

        $message->refresh();
        $this->assertNotNull($message->deleted_at);
        $this->assertSame($client->id, $message->deleted_by_user_id);
        $this->assertSame('Pesan yang akan ditarik.', $message->body);
        $this->assertDatabaseCount('chat_messages', 1);
        $this->assertDatabaseHas('audit_logs', ['event' => 'chat.message_deleted', 'auditable_id' => $message->id]);

        $this->expectException(DomainException::class);
        app(ChatMessageManagementService::class)->delete($thread, $message->id, $client);
    }

    public function test_deleted_unread_message_is_excluded_from_admin_inbox_count(): void
    {
        [$client, $adminUser, $thread] = $this->thread();
        $first = $this->message($thread, $client, 'Pesan pertama.');
        $this->message($thread, $client, 'Pesan kedua.');

        $this->actingAs($adminUser)
            ->get(route('admin.support.index'))
            ->assertSee('aria-label="2 pesan belum dibaca"', false);

        app(ChatMessageManagementService::class)->delete($thread, $first->id, $client);

        $this->actingAs($adminUser)
            ->get(route('admin.support.index'))
            ->assertSee('aria-label="1 pesan belum dibaca"', false)
            ->assertDontSee('Pesan pertama.');
    }

    public function test_archive_is_per_user_requires_a_read_thread_and_can_be_restored(): void
    {
        [$client, $adminUser, $thread] = $this->thread();
        $archive = app(ChatThreadArchiveService::class);

        $archive->archive($thread, $adminUser);
        $this->assertDatabaseHas('chat_thread_user_states', ['chat_thread_id' => $thread->id, 'user_id' => $adminUser->id]);

        $this->actingAs($adminUser)
            ->get(route('admin.support.index'))
            ->assertDontSee($thread->public_id);

        $this->actingAs($adminUser)
            ->get(route('admin.support.index', ['filter' => 'archived']))
            ->assertSee($thread->public_id)
            ->assertSee('Diarsipkan');

        $this->actingAs($client)->get(route('client.chat.show', $thread->public_id))->assertOk();

        $archive->archive($thread, $client);
        $this->actingAs($client)
            ->get(route('qna', ['conversation_filter' => 'archived']))
            ->assertSee('Arsip percakapan')
            ->assertSee(route('client.chat.show', $thread->public_id), false);
        $archive->unarchive($thread, $client);

        $this->actingAs($adminUser)
            ->post(route('admin.support.unarchive', $thread->public_id))
            ->assertRedirect();

        $this->assertNull(ChatThreadUserState::query()->where('chat_thread_id', $thread->id)->where('user_id', $adminUser->id)->value('archived_at'));

        $unreadThread = $this->generalThread($client, $thread->assignedAdmin);
        $this->message($unreadThread, $client, 'Masih belum dibaca.');
        $this->expectException(DomainException::class);
        $archive->archive($unreadThread, $adminUser);
    }

    public function test_new_incoming_or_outgoing_message_unarchives_only_the_relevant_participant_state(): void
    {
        [$client, $adminUser, $thread] = $this->thread();
        $archive = app(ChatThreadArchiveService::class);
        $archive->archive($thread, $adminUser);

        Livewire::actingAs($client)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->set('body', 'Pesan baru untuk admin.')
            ->call('send');

        $this->assertNull(ChatThreadUserState::query()->where('chat_thread_id', $thread->id)->where('user_id', $adminUser->id)->value('archived_at'));

        $archive->archive($thread, $client);
        Livewire::actingAs($client)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->set('body', 'Mengaktifkan arsip saya sendiri.')
            ->call('send');

        $this->assertNull(ChatThreadUserState::query()->where('chat_thread_id', $thread->id)->where('user_id', $client->id)->value('archived_at'));
    }

    /** @return array{User, User, ChatThread} */
    private function thread(): array
    {
        $client = User::factory()->create(['name' => 'Klien Sintetis']);
        $adminUser = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        $admin = Admin::create(['user_id' => $adminUser->id, 'email' => $adminUser->email, 'role' => UserRole::SUPER_ADMIN, 'is_active' => true]);
        $service = Service::factory()->create();
        $application = Application::create(['user_id' => $client->id, 'service_id' => $service->id, 'status' => ApplicationStatus::DRAFT, 'price_amount_snapshot' => 100000, 'currency' => 'IDR']);
        $thread = ChatThread::create(['application_id' => $application->id, 'client_user_id' => $client->id, 'assigned_admin_id' => $admin->id]);

        return [$client, $adminUser, $thread];
    }

    private function generalThread(User $client, Admin $admin): ChatThread
    {
        return ChatThread::create(['client_user_id' => $client->id, 'assigned_admin_id' => $admin->id, 'context_type' => ChatThreadType::GENERAL_SUPPORT]);
    }

    private function message(ChatThread $thread, User $sender, string $body, array $attributes = []): ChatMessage
    {
        return $thread->messages()->create(['sender_user_id' => $sender->id, 'body' => $body, ...$attributes]);
    }
}
