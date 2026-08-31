<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResultAvailableNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $applicationId,
        public readonly string $resultId,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Hasil layanan tersedia')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Hasil layanan Anda telah tersedia.')
            ->action('Lihat hasil layanan', route('client.applications.show', $this->applicationId))
            ->line('Masuk ke akun Bantu Daftarin Anda untuk melihat detail hasil secara aman.');
    }
}
