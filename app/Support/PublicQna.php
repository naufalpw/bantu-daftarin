<?php

namespace App\Support;

final class PublicQna
{
    /**
     * Product-approved QnA preview shared with the public landing page.
     *
     * @return list<array{id: string, category: string, question: string, answer: string, keywords: list<string>}>
     */
    public static function entries(): array
    {
        return HelpFaq::landingPreview();
    }
}
