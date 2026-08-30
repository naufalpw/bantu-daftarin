<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationUpdateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $subjectLine, public readonly string $messageLine)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject($this->subjectLine)->greeting('Halo '.$notifiable->name.',')->line($this->messageLine)->line('Silakan buka aplikasi Bantu Daftarin untuk melihat detailnya.');
    }
}
