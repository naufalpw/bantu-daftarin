<?php

namespace App\Notifications;

use App\Support\TransactionalEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->afterCommit();
    }

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

        return TransactionalEmail::make(
            subject: 'Verifikasi email BantuDaftarin',
            preheader: 'Selesaikan verifikasi email akun BantuDaftarin Anda.',
            greeting: TransactionalEmail::greetingFor($notifiable),
            title: 'Verifikasi email Anda',
            paragraphs: ['Verifikasi email untuk mengaktifkan akun BantuDaftarin Anda.'],
            actionText: 'Verifikasi email',
            actionUrl: $url,
            secondaryLines: [
                'Tautan verifikasi ini berlaku selama 60 menit.',
                'Jika Anda tidak membuat akun BantuDaftarin, abaikan email ini.',
            ],
        );
    }
}
