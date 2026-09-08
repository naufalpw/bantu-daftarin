<?php

namespace Tests\Feature\Chat;

use App\Enums\ApplicationStatus;
use App\Enums\ChatThreadType;
use App\Enums\UserRole;
use App\Jobs\SendChatUnreadEmail;
use App\Livewire\ChatThread as ChatThreadComponent;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
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
        Queue::fake();
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
        Queue::assertPushed(SendChatUnreadEmail::class, fn (SendChatUnreadEmail $job): bool => $job->threadId === $thread->id && $job->recipientId === $adminUser->id);
        $this->assertDatabaseHas('audit_logs', ['event' => 'chat.message_sent', 'auditable_id' => $message->id]);
    }

    public function test_chat_rejects_blank_or_whitespace_only_messages(): void
    {
        $client = User::factory()->create();
        $thread = $this->threadFor($client);

        Livewire::actingAs($client)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->set('body', '   ')
            ->call('send')
            ->assertHasErrors(['body' => 'required']);

        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_main_composer_uses_explicit_enter_handling_without_affecting_the_edit_textarea(): void
    {
        $client = User::factory()->create();
        $thread = $this->threadFor($client);

        $this->actingAs($client)
            ->get(route('client.chat.show', $thread->public_id))
            ->assertOk()
            ->assertSee('x-on:keydown=', false)
            ->assertSee('$event.shiftKey', false)
            ->assertDontSee('keydown.enter.exact', false);
    }

    public function test_read_receipt_status_has_one_named_accessible_wrapper(): void
    {
        $client = User::factory()->create();
        $adminUser = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        $admin = Admin::create(['user_id' => $adminUser->id, 'email' => $adminUser->email, 'role' => UserRole::SUPER_ADMIN, 'is_active' => true]);
        $thread = $this->threadFor($client, $admin);
        $message = $thread->messages()->create(['sender_user_id' => $client->id, 'body' => 'Pesan terbaca.']);
        $message->forceFill(['read_at' => now(), 'read_by_user_id' => $adminUser->id])->save();

        $this->actingAs($client)
            ->get(route('client.chat.show', $thread->public_id))
            ->assertOk()
            ->assertSee('role="img" aria-label="Dibaca"', false)
            ->assertSee('alt="" aria-hidden="true"', false);
    }

    public function test_opening_a_thread_marks_unread_messages_read_for_the_recipient(): void
    {
        $client = User::factory()->create();
        $adminUser = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        $admin = Admin::create(['user_id' => $adminUser->id, 'email' => $adminUser->email, 'role' => UserRole::SUPER_ADMIN, 'is_active' => true]);
        $thread = $this->threadFor($client, $admin);
        $message = $thread->messages()->create(['sender_user_id' => $adminUser->id, 'body' => 'Pesan synthetic']);

        Livewire::actingAs($client)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->assertDispatched('chat-unread-updated');

        $this->assertNotNull($message->fresh()->read_at);
        $this->assertSame($client->id, $message->fresh()->read_by_user_id);
    }

    public function test_opening_a_thread_marks_client_messages_read_for_an_admin(): void
    {
        $client = User::factory()->create();
        $adminUser = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        $admin = Admin::create(['user_id' => $adminUser->id, 'email' => $adminUser->email, 'role' => UserRole::SUPER_ADMIN, 'is_active' => true]);
        $thread = $this->threadFor($client, $admin);
        $message = $thread->messages()->create(['sender_user_id' => $client->id, 'body' => 'Pesan untuk admin.']);

        Livewire::actingAs($adminUser)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->assertDispatched('chat-unread-updated');

        $this->assertNotNull($message->fresh()->read_at);
        $this->assertSame($adminUser->id, $message->fresh()->read_by_user_id);
    }

    public function test_admin_can_use_assigned_chat_and_client_cannot_read_other_thread_messages(): void
    {
        Queue::fake();
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
        Queue::assertPushed(SendChatUnreadEmail::class, fn (SendChatUnreadEmail $job): bool => $job->threadId === $thread->id && $job->recipientId === $client->id);
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
            ->assertSee('Admin sedang mengetik...');

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

    public function test_chat_groups_messages_by_today_yesterday_and_older_dates(): void
    {
        $client = User::factory()->create();
        $thread = $this->threadFor($client);
        $today = Carbon::create(2026, 9, 6, 10, 0);

        $this->travelTo($today);
        try {
            ChatMessage::create(['chat_thread_id' => $thread->id, 'sender_user_id' => $client->id, 'body' => 'Pesan hari ini.', 'created_at' => $today->copy()->subHour()]);
            ChatMessage::create(['chat_thread_id' => $thread->id, 'sender_user_id' => $client->id, 'body' => 'Pesan kemarin.', 'created_at' => $today->copy()->subDay()]);
            ChatMessage::create(['chat_thread_id' => $thread->id, 'sender_user_id' => $client->id, 'body' => 'Pesan lama.', 'created_at' => $today->copy()->subDays(3)]);

            $this->actingAs($client)
                ->get(route('client.chat.show', $thread->public_id))
                ->assertOk()
                ->assertSee('Hari ini')
                ->assertSee('Kemarin')
                ->assertSee($today->copy()->subDays(3)->locale('id')->translatedFormat('j F Y'))
                ->assertSee('bd-chat-date-separator', false);
        } finally {
            $this->travelBack();
        }
    }

    public function test_admin_quick_reply_populates_and_appends_to_the_composer_without_sending(): void
    {
        $client = User::factory()->create();
        $adminUser = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        $admin = Admin::create(['user_id' => $adminUser->id, 'email' => $adminUser->email, 'role' => UserRole::SUPER_ADMIN, 'is_active' => true]);
        $thread = $this->threadFor($client, $admin);
        $documentReply = 'Dokumen Anda belum lengkap. Periksa kembali dokumen yang masih diperlukan pada pengajuan.';

        Livewire::actingAs($adminUser)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->assertSee('Balasan cepat')
            ->set('quickReply', 'documents-incomplete')
            ->assertSet('body', $documentReply)
            ->assertSet('quickReply', '')
            ->set('body', 'Catatan admin')
            ->set('quickReply', 'reupload-document')
            ->assertSet('body', "Catatan admin\n\nDokumen perlu diunggah ulang. Anda dapat menggantinya dari bagian Dokumen pada pengajuan.");

        Livewire::actingAs($client)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->assertDontSee('Balasan cepat');

        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_general_support_uses_context_safe_quick_reply_and_empty_state(): void
    {
        $client = User::factory()->create();
        $adminUser = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        $admin = Admin::create(['user_id' => $adminUser->id, 'email' => $adminUser->email, 'role' => UserRole::SUPER_ADMIN, 'is_active' => true]);
        $thread = $this->generalThreadFor($client, $admin);

        Livewire::actingAs($adminUser)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->assertSee('Belum ada percakapan')
            ->assertSee('Mulai percakapan dengan klien jika diperlukan.')
            ->set('quickReply', 'general-information')
            ->assertSet('body', 'Terima kasih sudah menghubungi kami. Ceritakan kendala yang Anda alami.')
            ->assertDontSee('Dokumen belum lengkap');

        $this->actingAs($client)
            ->get(route('client.chat.show', $thread->public_id))
            ->assertOk()
            ->assertSee('Belum ada percakapan')
            ->assertSee('bantuan umum');
        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_empty_application_support_explains_its_context_to_client_and_admin(): void
    {
        $client = User::factory()->create();
        $adminUser = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        $admin = Admin::create(['user_id' => $adminUser->id, 'email' => $adminUser->email, 'role' => UserRole::SUPER_ADMIN, 'is_active' => true]);
        $thread = $this->threadFor($client, $admin);

        $this->actingAs($client)
            ->get(route('client.chat.show', $thread->public_id))
            ->assertOk()
            ->assertSee('Belum ada percakapan')
            ->assertSee('Gunakan percakapan ini untuk pertanyaan tentang pengajuan NPWP Anda.');

        $this->actingAs($adminUser)
            ->get(route('admin.chat.show', $thread->public_id))
            ->assertOk()
            ->assertSee('Belum ada percakapan')
            ->assertSee('Gunakan percakapan ini untuk membahas pengajuan ini.');
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

    private function generalThreadFor(User $client, ?Admin $admin = null): ChatThread
    {
        return ChatThread::create([
            'client_user_id' => $client->id,
            'assigned_admin_id' => $admin?->id,
            'context_type' => ChatThreadType::GENERAL_SUPPORT,
        ]);
    }
}
