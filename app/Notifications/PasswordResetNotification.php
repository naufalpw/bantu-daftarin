<?php

namespace App\Notifications;

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

        return (new MailMessage)->subject('Reset password Bantu Daftarin')->greeting('Halo '.$notifiable->name.',')->line('Klik tombol berikut untuk membuat password baru.')->action('Reset password', $url)->line('Jika Anda tidak meminta reset password, abaikan email ini.');
    }
}
