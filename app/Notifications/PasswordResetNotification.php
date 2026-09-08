<?php

namespace App\Notifications;

use App\Support\TransactionalEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;

class PasswordResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public readonly string $encryptedToken;

    public function __construct(string $token)
    {
        $this->afterCommit();
        $this->encryptedToken = Crypt::encryptString($token);
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::route('password.reset', ['token' => Crypt::decryptString($this->encryptedToken), 'email' => $notifiable->getEmailForPasswordReset()]);

        return TransactionalEmail::make(
            subject: 'Atur ulang kata sandi BantuDaftarin',
            preheader: 'Gunakan tautan yang tersedia untuk mengatur ulang kata sandi akun Anda.',
            greeting: TransactionalEmail::greetingFor($notifiable),
            title: 'Atur ulang kata sandi',
            paragraphs: ['Kami menerima permintaan untuk mengatur ulang kata sandi akun BantuDaftarin Anda.'],
            actionText: 'Atur ulang kata sandi',
            actionUrl: $url,
            secondaryLines: [
                'Tautan ini berlaku selama 60 menit.',
                'Jika Anda tidak meminta pengaturan ulang, abaikan email ini.',
            ],
        );
    }
}
