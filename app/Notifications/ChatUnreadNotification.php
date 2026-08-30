<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChatUnreadNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $threadId)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Pesan baru di Bantu Daftarin')->greeting('Halo '.$notifiable->name.',')->line('Ada pesan baru terkait aplikasi Anda.')->line('Buka aplikasi Bantu Daftarin untuk membacanya.');
    }
}
