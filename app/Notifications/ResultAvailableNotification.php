<?php

namespace App\Notifications;

use App\Support\TransactionalEmail;
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
        public readonly ?string $serviceName = null,
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
            subject: 'Hasil pengajuan Anda sudah tersedia',
            preheader: 'Hasil pengajuan Anda sudah tersedia untuk ditinjau.',
            greeting: TransactionalEmail::greetingFor($notifiable),
            title: 'Hasil pengajuan tersedia',
            paragraphs: ['Hasil pengajuan Anda sudah tersedia untuk ditinjau.'],
            context: array_filter([
                'Layanan' => $this->serviceName,
                'ID Pengajuan' => TransactionalEmail::shortApplicationId($this->applicationId),
            ]),
            actionText: 'Lihat hasil pengajuan',
            actionUrl: route('client.applications.show', $this->applicationId),
            secondaryLines: ['Masuk ke akun BantuDaftarin untuk melihat detail hasil secara aman.'],
        );
    }
}
