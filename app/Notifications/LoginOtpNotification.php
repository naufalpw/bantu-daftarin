<?php

namespace App\Notifications;

use App\Support\TransactionalEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;

class LoginOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public readonly string $encryptedCode;

    public function __construct(string $code)
    {
        $this->afterCommit();
        $this->encryptedCode = Crypt::encryptString($code);
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return TransactionalEmail::make(
            subject: 'Kode verifikasi BantuDaftarin',
            preheader: 'Gunakan kode verifikasi untuk melanjutkan masuk ke akun BantuDaftarin Anda.',
            greeting: TransactionalEmail::greetingFor($notifiable),
            title: 'Kode verifikasi',
            paragraphs: ['Gunakan kode berikut untuk melanjutkan masuk ke akun BantuDaftarin Anda.'],
            secondaryLines: [
                'Kode ini berlaku selama 10 menit dan hanya dapat digunakan satu kali.',
                'Jangan bagikan kode ini kepada siapa pun.',
                'Jika Anda tidak mencoba masuk, abaikan email ini.',
            ],
            otpCode: Crypt::decryptString($this->encryptedCode),
        );
    }
}
