<?php

namespace Tests\Feature\Activity;

use App\Enums\ApplicationStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_client_sees_only_its_payment_process_and_order_activity(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $application = $this->applicationFor($owner, ApplicationStatus::UNDER_REVIEW);
        $otherApplication = $this->applicationFor($other);

        $payment = Payment::create([
            'application_id' => $application->id,
            'provider' => 'fake',
            'reference_id' => 'BD-ACTIVITY-OWNER',
            'amount' => 175000,
            'currency' => 'IDR',
            'payment_method' => PaymentMethod::BCA,
            'status' => PaymentStatus::PAID,
            'paid_at' => now(),
        ]);
        Payment::create([
            'application_id' => $otherApplication->id,
            'provider' => 'fake',
            'reference_id' => 'BD-ACTIVITY-OTHER',
            'amount' => 999000,
            'currency' => 'IDR',
            'payment_method' => PaymentMethod::BRI,
            'status' => PaymentStatus::PAID,
        ]);
        ApplicationStatusHistory::create([
            'application_id' => $application->id,
            'from_status' => ApplicationStatus::AWAITING_PAYMENT->value,
            'to_status' => ApplicationStatus::UNDER_REVIEW->value,
            'actor_type' => 'admin',
            'reason' => 'Synthetic activity test',
            'created_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('client.activity.index'))
            ->assertRedirect(route('client.applications.index'));

        $this->actingAs($owner)
            ->get(route('client.applications.show', $application->public_id).'#proses')
            ->assertOk()
            ->assertSee($payment->reference_id)
            ->assertSee('Pembayaran berhasil')
            ->assertSee('Dokumen sedang diperiksa')
            ->assertSee($application->service->name)
            ->assertDontSee('BD-ACTIVITY-OTHER')
            ->assertDontSee('999.000');
    }

    public function test_browser_query_cannot_fake_payment_activity_status(): void
    {
        $owner = User::factory()->create();
        $application = $this->applicationFor($owner);
        Payment::create([
            'application_id' => $application->id,
            'provider' => 'fake',
            'reference_id' => 'BD-ACTIVITY-PENDING',
            'amount' => 175000,
            'currency' => 'IDR',
            'payment_method' => PaymentMethod::QRIS,
            'status' => PaymentStatus::PENDING,
        ]);

        $this->actingAs($owner)
            ->get(route('client.activity.index', ['status' => 'PAID']))
            ->assertRedirect(route('client.applications.index'));

        $this->actingAs($owner)
            ->get(route('client.applications.show', $application->public_id).'#pembayaran')
            ->assertOk()
            ->assertSee('Menunggu pembayaran')
            ->assertDontSee('Pembayaran berhasil');
    }

    public function test_client_can_open_own_activity_detail_but_not_another_clients_detail(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $application = $this->applicationFor($owner, ApplicationStatus::COMPLETED);
        $otherApplication = $this->applicationFor($other);

        $this->actingAs($owner)
            ->get(route('client.activity.show', $application->public_id))
            ->assertRedirect(route('client.applications.show', $application->public_id).'#proses');

        $this->actingAs($owner)
            ->get(route('client.activity.show', $otherApplication->public_id))
            ->assertForbidden();
    }

    public function test_activity_keeps_failed_and_expired_payment_states_from_payment_records(): void
    {
        $owner = User::factory()->create();
        $application = $this->applicationFor($owner);

        foreach ([PaymentStatus::FAILED, PaymentStatus::EXPIRED] as $index => $status) {
            Payment::create([
                'application_id' => $application->id,
                'provider' => 'fake',
                'reference_id' => 'BD-ACTIVITY-'.$status->value,
                'amount' => 175000 + $index,
                'currency' => 'IDR',
                'payment_method' => PaymentMethod::BRI,
                'status' => $status,
            ]);
        }

        $this->actingAs($owner)
            ->get(route('client.activity.index'))
            ->assertRedirect(route('client.applications.index'));

        $this->actingAs($owner)
            ->get(route('client.applications.show', $application->public_id).'#proses')
            ->assertOk()
            ->assertSee('Pembayaran gagal')
            ->assertSee('Pembayaran kedaluwarsa');
    }

    private function applicationFor(User $user, ApplicationStatus $status = ApplicationStatus::DRAFT): Application
    {
        $service = Service::factory()->create(['name' => 'NPWP Personal Synthetic']);

        return Application::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => $status,
            'price_amount_snapshot' => 175000,
            'currency' => 'IDR',
        ]);
    }
}
