<?php

namespace App\Support;

use App\Models\ChatMessage;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class ChatPresentation
{
    /**
     * @param  Collection<int, ChatMessage>  $messages
     * @return Collection<int, array{label: string, messages: Collection<int, ChatMessage>}>
     */
    public static function groupedMessages(Collection $messages): Collection
    {
        return $messages
            ->groupBy(fn ($message) => $message->created_at->toDateString())
            ->map(fn (Collection $group, string $date): array => [
                'label' => self::dateLabel($group->first()->created_at),
                'messages' => $group,
            ])
            ->values();
    }

    public static function dateLabel(CarbonInterface $date): string
    {
        if ($date->isToday()) {
            return 'Hari ini';
        }

        if ($date->isYesterday()) {
            return 'Kemarin';
        }

        return $date->locale('id')->translatedFormat('j F Y');
    }
}
