<?php

namespace App\Notifications;

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
        $this->encryptedCode = Crypt::encryptString($code);
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode OTP Bantu Daftarin')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Gunakan kode OTP berikut untuk melanjutkan login:')
            ->line(Crypt::decryptString($this->encryptedCode))
            ->line('Kode berlaku terbatas dan hanya dapat digunakan satu kali.')
            ->line('Jika Anda tidak merasa melakukan login, abaikan email ini.');
    }
}
