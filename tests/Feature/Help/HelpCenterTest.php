<?php

namespace Tests\Feature\Help;

use App\Enums\ApplicationStatus;
use App\Enums\ChatThreadType;
use App\Enums\UserRole;
use App\Livewire\ChatThread as ChatThreadComponent;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ChatThread;
use App\Models\Service;
use App\Models\User;
use App\Support\HelpFaq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class HelpCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_help_center_renders_search_categories_and_shared_faq_entries(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('qna'))
            ->assertOk()
            ->assertSee('Pusat Bantuan')
            ->assertSee('data-help-search', false)
            ->assertSee('Cari pertanyaan, mis. dokumen, pembayaran, revisi…')
            ->assertSee('Data &amp; Dokumen', false)
            ->assertSee('Privasi &amp; Bantuan', false)
            ->assertSee('Pertanyaan tidak ditemukan')
            ->assertSee('Hapus pencarian')
            ->assertSee('Hubungi Admin')
            ->assertSee(route('client.help.chat.store'), false);

        $this->assertCount(24, HelpFaq::entries());
        $this->assertSame(24, substr_count($response->getContent(), 'data-help-item'));
        $response->assertDontSee('Lapor Pajak aktif')
            ->assertDontSee('selesai dalam satu hari')
            ->assertDontSee('dijamin 100% aman')
            ->assertDontSee('Live Chat');
    }

    public function test_help_search_source_matches_question_keyword_category_and_combination(): void
    {
        $keywordMatches = array_column(HelpFaq::filter('upload ulang', 'documents'), 'id');
        $this->assertContains('ganti-dokumen', $keywordMatches);
        $this->assertContains('revisi-dokumen', $keywordMatches);
        $this->assertContains('durasi-pengajuan', array_column(HelpFaq::filter('berapa lama'), 'id'));
        $this->assertCount(4, HelpFaq::filter('', 'payments'));
        $this->assertSame(['metode-pembayaran'], array_column(HelpFaq::filter('qris', 'payments'), 'id'));
        $this->assertSame([], HelpFaq::filter('pertanyaan yang tidak tersedia', 'results'));
    }

    public function test_help_center_lists_only_owned_applications_with_presented_status(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $service = Service::factory()->create(['name' => 'NPWP Perseorangan']);
        $owned = $this->applicationWithThread($owner, $service, ApplicationStatus::REVISION_REQUIRED);
        $second = $this->applicationWithThread($owner, $service, ApplicationStatus::UNDER_REVIEW);
        $foreign = $this->applicationWithThread($other, $service, ApplicationStatus::UNDER_REVIEW);

        $this->actingAs($owner)->get(route('qna'))
            ->assertOk()
            ->assertSee('Dokumen perlu diperbaiki')
            ->assertSee('Dokumen sedang diperiksa')
            ->assertSee(route('client.chat.show', $owned->chatThread->public_id), false)
            ->assertSee(route('client.chat.show', $second->chatThread->public_id), false)
            ->assertDontSee(route('client.chat.show', $foreign->chatThread->public_id), false);
    }

    public function test_help_center_has_intentional_no_application_state(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('qna'))
            ->assertOk()
            ->assertSee('Belum ada pengajuan')
            ->assertSee('Chat terkait pengajuan tersedia setelah Anda membuat pengajuan.')
            ->assertSee(route('client.services.index'), false);
    }

    public function test_general_support_requires_authentication_and_reuses_one_thread_per_client(): void
    {
        $this->post(route('client.help.chat.store'))->assertRedirect(route('login'));

        $client = User::factory()->create();
        $first = $this->actingAs($client)->post(route('client.help.chat.store'));
        $thread = ChatThread::query()->sole();

        $first->assertRedirect(route('client.chat.show', $thread->public_id));
        $this->assertSame(ChatThreadType::GENERAL_SUPPORT, $thread->context_type);
        $this->assertNull($thread->application_id);

        $this->actingAs($client)->post(route('client.help.chat.store'))
            ->assertRedirect(route('client.chat.show', $thread->public_id));
        $this->assertDatabaseCount('chat_threads', 1);
    }

    public function test_general_support_is_owned_and_admin_can_open_and_answer_it(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $adminUser = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        $admin = Admin::create(['user_id' => $adminUser->id, 'email' => $adminUser->email, 'role' => UserRole::SUPER_ADMIN, 'is_active' => true]);

        $this->actingAs($owner)->post(route('client.help.chat.store'));
        $thread = ChatThread::query()->sole();

        $this->actingAs($owner)->get(route('client.chat.show', $thread->public_id))
            ->assertOk()
            ->assertSee('Percakapan dengan Tim Bantu Daftarin')
            ->assertDontSee('ID …');
        $this->actingAs($other)->get(route('client.chat.show', $thread->public_id))->assertForbidden();
        $this->actingAs($adminUser)->get(route('admin.chat.show', $thread->public_id))
            ->assertOk()
            ->assertSee('Bantuan Umum')
            ->assertSee($owner->name);

        Livewire::actingAs($adminUser)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->set('body', 'Balasan bantuan umum sintetis')
            ->call('send');

        $this->assertSame($admin->id, $thread->fresh()->assigned_admin_id);
        $this->assertDatabaseHas('chat_messages', ['chat_thread_id' => $thread->id, 'body' => 'Balasan bantuan umum sintetis']);
    }

    public function test_application_threads_keep_their_context_and_are_unaffected_by_general_support(): void
    {
        $client = User::factory()->create();
        $application = $this->applicationWithThread($client, Service::factory()->create(), ApplicationStatus::DRAFT);

        $this->assertSame(ChatThreadType::APPLICATION, $application->chatThread->context_type);
        $this->assertNotNull($application->chatThread->application_id);

        $this->actingAs($client)->post(route('client.help.chat.store'));

        $this->assertDatabaseCount('chat_threads', 2);
        $this->assertDatabaseHas('chat_threads', [
            'id' => $application->chatThread->id,
            'context_type' => ChatThreadType::APPLICATION->value,
            'application_id' => $application->id,
        ]);
    }

    public function test_admin_support_list_distinguishes_general_and_application_conversations(): void
    {
        $client = User::factory()->create(['name' => 'Klien Sintetis']);
        $application = $this->applicationWithThread($client, Service::factory()->create(['name' => 'NPWP Perseorangan']), ApplicationStatus::DRAFT);
        $general = ChatThread::create(['client_user_id' => $client->id, 'context_type' => ChatThreadType::GENERAL_SUPPORT]);
        $general->messages()->create(['sender_user_id' => $client->id, 'body' => 'Pertanyaan umum sintetis']);
        $general->update(['last_message_at' => now()]);

        $adminUser = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        Admin::create(['user_id' => $adminUser->id, 'email' => $adminUser->email, 'role' => UserRole::SUPER_ADMIN, 'is_active' => true]);

        $this->actingAs($adminUser)->get(route('admin.support.index'))
            ->assertOk()
            ->assertSee('Bantuan Umum')
            ->assertSee('Pengajuan')
            ->assertSee('Pertanyaan umum sintetis')
            ->assertSee(route('admin.chat.show', $general->public_id), false)
            ->assertSee(route('admin.chat.show', $application->chatThread->public_id), false);
    }

    private function applicationWithThread(User $client, Service $service, ApplicationStatus $status): Application
    {
        $application = Application::factory()->create([
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => $status,
            'price_amount_snapshot' => $service->price_amount,
            'currency' => $service->currency,
        ]);
        $application->chatThread()->create(['client_user_id' => $client->id]);

        return $application->load('chatThread');
    }
}
