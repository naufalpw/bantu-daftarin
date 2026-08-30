<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'publicId' => $notifiable->public_id,
            'hash' => sha1($notifiable->getEmailForVerification()),
        ]);

        return (new MailMessage)
            ->subject('Verifikasi email Bantu Daftarin')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Klik tombol berikut untuk memverifikasi email Anda.')
            ->action('Verifikasi email', $url)
            ->line('Tautan verifikasi berlaku selama 60 menit.');
    }
}
