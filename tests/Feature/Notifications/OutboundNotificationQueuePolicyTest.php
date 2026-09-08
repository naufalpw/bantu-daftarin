<?php

namespace Tests\Feature\Notifications;

use App\Jobs\SendChatUnreadEmail;
use App\Notifications\ApplicationUpdateNotification;
use App\Notifications\ChatUnreadNotification;
use App\Notifications\LoginOtpNotification;
use App\Notifications\PasswordResetNotification;
use App\Notifications\ResultAvailableNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Tests\TestCase;

class OutboundNotificationQueuePolicyTest extends TestCase
{
    public function test_outbound_notifications_and_chat_delivery_job_use_explicit_after_commit_delivery(): void
    {
        $queuedWork = [
            new LoginOtpNotification('123456'),
            new VerifyEmailNotification,
            new PasswordResetNotification('reset-token'),
            new ChatUnreadNotification('00000000-0000-4000-8000-000000000001'),
            new ApplicationUpdateNotification('Subject', 'Message'),
            new ResultAvailableNotification('00000000-0000-4000-8000-000000000002', '00000000-0000-4000-8000-000000000003'),
            new SendChatUnreadEmail(1, 2, '00000000-0000-4000-8000-000000000004'),
        ];

        foreach ($queuedWork as $work) {
            $this->assertInstanceOf(ShouldQueue::class, $work);
            $this->assertTrue($work->afterCommit);
        }
    }
}
