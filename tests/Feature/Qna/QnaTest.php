<?php

namespace Tests\Feature\Qna;

use Tests\TestCase;

class QnaTest extends TestCase
{
    public function test_qna_is_public_and_contains_the_questions_and_answers_available_in_figma(): void
    {
        $this->get(route('qna'))
            ->assertOk()
            ->assertSee('QnA')
            ->assertSee('Apa itu Bantudaftarin?')
            ->assertSee('Dokumen apa saja yang diperlukan untuk membuat NPWP?')
            ->assertSee('Berapa lama proses pembuatan NPWP?')
            ->assertSee('Apakah data dan dokumen saya aman?')
            ->assertSee('Bagaimana jika saya mengalami kendala saat proses pendaftaran?')
            ->assertSee('Bantudaftarin adalah layanan yang membantu proses administrasi perpajakan secara online')
            ->assertSee('Dokumen yang dibutuhkan bergantung pada jenis layanan yang dipilih.')
            ->assertSee('aria-expanded="true"', false)
            ->assertSee('aria-expanded="false"', false);
    }

    public function test_site_header_qna_link_points_to_the_public_qna_route(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="'.route('qna').'"', false);
    }
}
