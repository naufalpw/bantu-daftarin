<?php

namespace App\Notifications;

use App\Support\TransactionalEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationUpdateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $subjectLine,
        public readonly string $messageLine,
        public readonly ?string $applicationId = null,
        public readonly ?string $serviceName = null,
        public readonly ?string $title = null,
        public readonly ?string $preheader = null,
        public readonly ?string $detailLabel = null,
        public readonly ?string $detailValue = null,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $context = array_filter([
            'Layanan' => $this->serviceName,
            'ID Pengajuan' => TransactionalEmail::shortApplicationId($this->applicationId),
        ]);

        if ($this->detailLabel !== null && $this->detailValue !== null) {
            $context[$this->detailLabel] = $this->detailValue;
        }

        return TransactionalEmail::make(
            subject: $this->subjectLine,
            preheader: $this->preheader ?? 'Ada pembaruan pada pengajuan Anda.',
            greeting: TransactionalEmail::greetingFor($notifiable),
            title: $this->title ?? 'Pembaruan pengajuan',
            paragraphs: [$this->messageLine],
            context: $context,
            actionText: $this->applicationId === null ? null : 'Buka pengajuan',
            actionUrl: $this->applicationId === null ? null : route('client.applications.show', $this->applicationId),
            secondaryLines: $this->applicationId === null ? [] : ['Buka pengajuan untuk melihat detail terbaru.'],
        );
    }
}
