<?php

namespace Tests\Feature\Public;

use App\Enums\UserRole;
use App\Models\User;
use Tests\TestCase;

class LandingPagePhaseCTest extends TestCase
{
    public function test_guest_sees_the_factual_landing_page_with_public_metadata(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<title>Bantu Daftarin | Bantuan administrasi NPWP</title>', false)
            ->assertSee('<meta name="description" content="Bantuan administrasi untuk menyiapkan pengajuan NPWP Perseorangan dan NPWP Badan Usaha.">', false)
            ->assertSee('#BantuUrusanPajakJadiMudah')
            ->assertSee('Urusan NPWP jadi lebih terarah')
            ->assertSee('images/figma/home/logo-color.png', false)
            ->assertSee('Data &amp; Dokumen', false)
            ->assertSee('Pantau Status', false)
            ->assertSee('Bantuan Sesuai', false)
            ->assertDontSee('pb-home-hero__note', false)
            ->assertSee('Pilih layanan')
            ->assertSee('Bagaimana cara mendaftarkan?')
            ->assertSee('Lengkapi data')
            ->assertSee('Unggah dokumen')
            ->assertSee('Pembayaran &amp; pemeriksaan', false)
            ->assertSee('Pantau proses &amp; hasil', false)
            ->assertSee('Satu ruang untuk memantau pengajuan')
            ->assertSee('images/figma/home/steps-flow.png', false)
            ->assertDontSee('Pahami alur sebelum memulai')
            ->assertSee('Lapor Pajak')
            ->assertSee('Segera hadir');
    }

    public function test_authenticated_client_is_redirected_to_the_client_dashboard_from_home(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertRedirect(route('client.dashboard'));
    }

    public function test_authenticated_admin_is_redirected_to_the_admin_dashboard_from_home(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);

        $this->actingAs($admin)
            ->get(route('home'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_public_navigation_and_account_ctas_point_to_real_destinations(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="#beranda"', false)
            ->assertSee('href="#layanan"', false)
            ->assertSee('href="#cara-kerja"', false)
            ->assertSee('href="#qna"', false)
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee('href="'.route('register').'"', false);
    }

    public function test_landing_page_has_no_unsupported_features_or_social_proof(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Lapor Sekarang')
            ->assertDontSee('Live Chat')
            ->assertDontSee('Andi Pratama')
            ->assertDontSee('100% online')
            ->assertDontSee('Aman &amp; Terpercaya', false)
            ->assertDontSee('Keranjang');
    }

    public function test_landing_page_labels_its_demo_testimonials_and_exposes_manual_controls(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Testimoni Klien')
            ->assertSee('Contoh testimoni untuk tampilan demonstrasi.')
            ->assertSee('Raka Pradana')
            ->assertSee('Siti Maharani')
            ->assertSee('Dimas Kurniawan')
            ->assertSee('Nadia Prameswari')
            ->assertSee('data-testimonial-previous', false)
            ->assertSee('data-testimonial-next', false)
            ->assertSee('aria-label="Testimoni sebelumnya"', false)
            ->assertSee('aria-label="Testimoni berikutnya"', false)
            ->assertDontSee('Informasi penting tetap dekat dengan proses Anda')
            ->assertDontSee('Dalam waktu singkat NPWP saya sudah selesai.')
            ->assertDontSee('Pendafat Klien yang telah menggunakan layanan');
    }

    public function test_services_are_a_coherent_public_catalog_with_a_noninteractive_coming_soon_option(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Pilih layanan sesuai kebutuhan pengajuan')
            ->assertDontSee('Biaya dan persyaratan ditampilkan sebelum Anda memulai pengajuan.')
            ->assertSee('pb-service-preview--personal', false)
            ->assertSee('pb-service-preview--business', false)
            ->assertSee('images/figma/home/business-icon.svg', false)
            ->assertSee('images/figma/home/tax-icon.png', false)
            ->assertSee('pb-service-preview--coming-soon', false)
            ->assertSee('Layanan pelaporan pajak sedang dipersiapkan.')
            ->assertSee('Segera hadir')
            ->assertDontSee('Lapor Sekarang');
    }

    public function test_landing_page_has_five_product_correct_qna_entries_and_an_accessible_mobile_menu(): void
    {
        $response = $this->get(route('home'))
            ->assertOk()
            ->assertSee('Apa itu BantuDaftarin?')
            ->assertSee('Dokumen apa yang perlu disiapkan?')
            ->assertSee('Berapa lama proses pengajuan NPWP?')
            ->assertSee('Bagaimana data dan dokumen saya disimpan?')
            ->assertSee('Bagaimana jika saya mengalami kendala saat proses pengajuan?')
            ->assertSee('data-home-qna-question', false)
            ->assertSee('data-home-qna-answer-title', false)
            ->assertSee('data-home-qna-answer-body', false)
            ->assertSee('Dokumen pengajuan disimpan melalui penyimpanan privat.')
            ->assertDontSee('Lapor Pajak aktif')
            ->assertDontSee('Live Chat')
            ->assertDontSee('Lihat semua pertanyaan')
            ->assertSee('data-public-menu-button', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('aria-controls="public-menu"', false)
            ->assertSee('id="public-menu"', false);

        $this->assertSame(5, substr_count($response->getContent(), 'data-home-qna-question'));
        $this->assertSame(5, substr_count($response->getContent(), 'class="pb-home-qna__question"'));
    }
}
