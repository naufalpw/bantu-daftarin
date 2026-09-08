<?php

namespace App\Notifications;

use App\Support\TransactionalEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChatUnreadNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $threadId,
        public readonly ?string $conversationContext = null,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return TransactionalEmail::make(
            subject: 'Ada pesan baru di BantuDaftarin',
            preheader: 'Anda memiliki pesan yang belum dibaca di BantuDaftarin.',
            greeting: TransactionalEmail::greetingFor($notifiable),
            title: 'Ada pesan baru',
            paragraphs: ['Anda memiliki pesan yang belum dibaca.'],
            context: $this->conversationContext === null ? [] : ['Percakapan' => $this->conversationContext],
            actionText: 'Buka percakapan',
            actionUrl: $this->conversationUrl($notifiable),
            secondaryLines: ['Isi percakapan tidak ditampilkan di email untuk menjaga privasi Anda.'],
        );
    }

    private function conversationUrl(object $notifiable): string
    {
        return route($notifiable->isAdmin() ? 'admin.chat.show' : 'client.chat.show', $this->threadId);
    }
}
