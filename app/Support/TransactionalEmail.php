<?php

namespace App\Support;

use Illuminate\Notifications\Messages\MailMessage;

class TransactionalEmail
{
    public static function greetingFor(object $notifiable): string
    {
        $name = trim((string) ($notifiable->name ?? ''));

        return $name === '' ? 'Halo.' : 'Halo, '.$name.'.';
    }

    public static function shortApplicationId(?string $publicId): ?string
    {
        if ($publicId === null || $publicId === '') {
            return null;
        }

        return '…'.strtoupper(substr($publicId, -6));
    }

    /**
     * Build a project-owned HTML and plain-text notification message.
     *
     * The delivery channel, queue, recipients, and after-commit policy remain
     * owned by each notification. This helper only supplies presentation data.
     *
     * @param  array<string, string>  $context
     * @param  array<int, string>  $paragraphs
     * @param  array<int, string>  $secondaryLines
     */
    public static function make(
        string $subject,
        string $preheader,
        string $greeting,
        string $title,
        array $paragraphs = [],
        array $context = [],
        ?string $actionText = null,
        ?string $actionUrl = null,
        array $secondaryLines = [],
        ?string $otpCode = null,
    ): MailMessage {
        $data = [
            'preheader' => $preheader,
            'title' => $title,
            'paragraphs' => $paragraphs,
            'context' => $context,
            'secondaryLines' => $secondaryLines,
            'otpCode' => $otpCode,
        ];

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting($greeting);

        foreach ($paragraphs as $paragraph) {
            $message->line($paragraph);
        }

        if ($actionText !== null && $actionUrl !== null) {
            $message->action($actionText, $actionUrl);
        }

        foreach ($secondaryLines as $line) {
            $message->line($line);
        }

        return $message
            ->view('mail.transactional', $data)
            ->text('mail.transactional-text', $data);
    }
}
