<?php

namespace Tests\Feature\Qna;

use App\Support\HelpFaq;
use Tests\TestCase;

class QnaTest extends TestCase
{
    public function test_public_qna_uses_the_shared_product_correct_help_source(): void
    {
        $response = $this->get(route('qna'))
            ->assertOk()
            ->assertSee('Pusat Bantuan')
            ->assertSee('Pertanyaan umum')
            ->assertSee('Apa itu BantuDaftarin?')
            ->assertSee('Dokumen apa yang perlu disiapkan?')
            ->assertSee('Berapa lama proses pengajuan NPWP?')
            ->assertSee('Bagaimana data dan dokumen saya disimpan?')
            ->assertSee('Bagaimana jika saya mengalami kendala saat proses pengajuan?')
            ->assertSee('BantuDaftarin bukan portal resmi pemerintah')
            ->assertSee('Dokumen pengajuan disimpan melalui penyimpanan privat.')
            ->assertSee('<details', false)
            ->assertDontSee('Lapor Pajak aktif')
            ->assertDontSee('Live Chat')
            ->assertDontSee('dijamin 100% aman');

        $this->assertSame(count(HelpFaq::entries()), substr_count($response->getContent(), 'data-help-item'));
    }

    public function test_site_header_qna_link_points_to_the_landing_qna_section(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="#qna"', false);
    }
}
