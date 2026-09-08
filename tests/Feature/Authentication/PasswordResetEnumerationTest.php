<?php

namespace Tests\Feature\Authentication;

use App\Models\User;
use App\Notifications\PasswordResetNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetEnumerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_known_and_unknown_email_receive_the_same_public_acknowledgement(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'known-reset@example.test']);

        $knownResponse = $this->post(route('password.email'), ['email' => $user->email]);
        $knownResponse->assertSessionHas('status', __('passwords.sent_if_registered'));
        $knownStatus = $knownResponse->getSession()->get('status');

        Notification::assertSentTo($user, PasswordResetNotification::class);

        $unknownResponse = $this->post(route('password.email'), ['email' => 'unknown-reset@example.test']);
        $unknownResponse->assertSessionHas('status', $knownStatus);

        Notification::assertCount(1);
    }

    public function test_password_reset_request_rate_limit_remains_enforced(): void
    {
        Notification::fake();
        $email = 'rate-limited-reset@example.test';

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('password.email'), ['email' => $email])->assertRedirect();
        }

        $this->post(route('password.email'), ['email' => $email])->assertTooManyRequests();
        Notification::assertNothingSent();
    }
}
