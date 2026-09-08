<?php

namespace Tests\Feature\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\ChatThreadType;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Notifications\LoginOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminPhaseBTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_uses_the_admin_surface_and_explains_otp(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Masuk sebagai Super Admin')
            ->assertSee('Masuk dan kirim OTP')
            ->assertSee('kode OTP dikirim ke email terdaftar')
            ->assertSee(route('admin.login.store'), false)
            ->assertDontSee('Pengguna baru?');
    }

    public function test_admin_otp_uses_the_same_admin_authentication_surface(): void
    {
        Notification::fake();
        $admin = $this->admin('otp-admin@example.test');

        $this->post(route('admin.login.store'), [
            'email' => $admin->user->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.otp'));

        $this->get(route('admin.otp'))
            ->assertOk()
            ->assertSee('VERIFIKASI ADMIN')
            ->assertSee('Masukkan kode OTP')
            ->assertSee('class="bd-otp-input"', false)
            ->assertSee(route('auth.otp.verify'), false)
            ->assertSee(route('auth.otp.resend'), false);

        Notification::assertSentTo($admin->user, LoginOtpNotification::class);
    }

    public function test_client_cannot_access_the_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_dashboard_uses_exact_operational_states_and_unread_client_messages(): void
    {
        $admin = $this->admin();
        $client = User::factory()->create(['name' => 'Klien Sintetis']);
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL', 'name' => 'NPWP Perseorangan']);

        foreach ([
            ApplicationStatus::DOCUMENTS_SUBMITTED,
            ApplicationStatus::DOCUMENTS_SUBMITTED,
            ApplicationStatus::REVISION_SUBMITTED,
            ApplicationStatus::RESULT_REVIEW,
            ApplicationStatus::IN_PROGRESS,
        ] as $status) {
            Application::factory()->create([
                'user_id' => $client->id,
                'service_id' => $service->id,
                'status' => $status,
            ]);
        }

        $thread = ChatThread::create([
            'client_user_id' => $client->id,
            'context_type' => ChatThreadType::GENERAL_SUPPORT,
            'last_message_at' => now(),
        ]);
        ChatMessage::create(['chat_thread_id' => $thread->id, 'sender_user_id' => $client->id, 'body' => 'Pesan klien belum dibaca.']);
        ChatMessage::create(['chat_thread_id' => $thread->id, 'sender_user_id' => $admin->user->id, 'body' => 'Balasan admin yang belum dibaca klien.']);

        $this->actingAs($admin->user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('attention', [
                'review' => 2,
                'revision' => 1,
                'result' => 1,
                'support' => 1,
            ])
            ->assertViewHas('priorityQueue', fn ($queue): bool => $queue->count() === 4)
            ->assertViewHas('period', 30)
            ->assertSee('Ruang kerja admin')
            ->assertSee('Perlu ditindaklanjuti')
            ->assertSee('AKTIVITAS OPERASIONAL')
            ->assertSee('Antrian prioritas')
            ->assertDontSee('Aktivitas Pengunjung')
            ->assertSee('NPWP Perseorangan');
    }

    public function test_dashboard_aggregates_canonical_operational_events_without_counting_payment_twice(): void
    {
        $admin = $this->admin();
        $client = User::factory()->create();
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL', 'name' => 'NPWP Perseorangan']);
        $application = Application::factory()->create(['user_id' => $client->id, 'service_id' => $service->id]);
        $now = now()->setTime(10, 0);

        ApplicationStatusHistory::create([
            'application_id' => $application->id,
            'to_status' => ApplicationStatus::DOCUMENTS_SUBMITTED,
            'actor_type' => 'user',
            'created_at' => $now->copy()->subDays(2),
        ]);
        ApplicationStatusHistory::create([
            'application_id' => $application->id,
            'to_status' => ApplicationStatus::PAYMENT_CONFIRMED,
            'actor_type' => 'system',
            'created_at' => $now->copy()->subDay(),
        ]);
        Payment::create([
            'application_id' => $application->id,
            'provider' => 'fake',
            'reference_id' => 'DASHBOARD-CANONICAL-PAYMENT',
            'amount' => 150000,
            'currency' => 'IDR',
            'status' => PaymentStatus::PAID,
            'paid_at' => $now->copy()->subDay(),
        ]);

        $this->actingAs($admin->user)
            ->get(route('admin.dashboard', ['period' => 7]))
            ->assertOk()
            ->assertViewHas('period', 7)
            ->assertViewHas('activity', fn (array $activity): bool => $activity['total'] === 2 && $activity['points']->count() === 7)
            ->assertSee('Pembayaran dikonfirmasi')
            ->assertSee('7 hari terakhir')
            ->assertDontSee('visitor')
            ->assertDontSee('Persentase');
    }

    public function test_dashboard_uses_honest_empty_operational_states(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin->user)
            ->get(route('admin.dashboard', ['period' => 90]))
            ->assertOk()
            ->assertViewHas('period', 90)
            ->assertSee('Belum ada aktivitas pada periode ini')
            ->assertSee('Semua pekerjaan prioritas sudah ditangani');
    }

    public function test_admin_sidebar_contains_only_real_admin_routes(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin->user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.dashboard'), false)
            ->assertSee(route('admin.applications.index'), false)
            ->assertSee(route('admin.documents.index'), false)
            ->assertSee(route('admin.support.index'), false)
            ->assertSee(route('admin.activity.index'), false)
            ->assertSee(route('admin.users.index'), false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('Keluar')
            ->assertDontSee('Pesanan produk')
            ->assertDontSee('Get Help')
            ->assertDontSee('Settings')
            ->assertDontSee('My Wallets');
    }

    private function admin(string $email = 'admin-phase-b@example.test'): Admin
    {
        $user = User::factory()->create([
            'name' => 'Admin Sintetis',
            'email' => $email,
            'password' => 'password',
            'role' => UserRole::SUPER_ADMIN,
        ]);

        return Admin::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);
    }
}
