@extends('layouts.marketing')

@section('content')
<div class="bd-home" data-node-id="208:10488" data-name="BANTU DAFTARIN LANDING PAGE">
    <x-site-header />

    <main>
        <section class="bd-home__hero" aria-labelledby="home-title">
            <div class="bd-home__hero-copy">
                <img class="bd-home__hero-brand" src="{{ asset('images/figma/home/hero-tag.png') }}" alt="Bantudaftarin">
                <span class="bd-home__hero-tag">#BantuUrusanPajakJadiMudah</span>
                <h1 id="home-title" class="bd-home__hero-title">ursan pajak jadi<br>mudah dan cepat</h1>
                <p class="bd-home__hero-description">Layanan online yang membantu kamu<br>mendaftarkan NPWP dan lapor pajak<br>tanpa ribet, 100% online.</p>

                <div class="bd-home__features" aria-label="Keunggulan layanan">
                    <div class="bd-home__feature">
                        <img src="{{ asset('images/figma/home/trust-icon.svg') }}" alt="">
                        <span>Aman &amp;<br>Terpercaya</span>
                    </div>
                    <div class="bd-home__feature">
                        <img src="{{ asset('images/figma/home/process-icon.svg') }}" alt="">
                        <span>Proses Cepat<br>&amp; Mudah</span>
                    </div>
                    <div class="bd-home__feature">
                        <img src="{{ asset('images/figma/home/support-icon.svg') }}" alt="">
                        <span>Tim Support<br>Siap Bantu</span>
                    </div>
                </div>
            </div>

            <div class="bd-home__hero-art" aria-hidden="true">
                <img src="{{ asset('images/figma/home/hero-image.png') }}" alt="">
            </div>
        </section>

        <section class="bd-home__services" aria-label="Layanan">
            <article class="bd-home__service-card">
                <div class="bd-home__service-icon">
                    <img src="{{ asset('images/figma/home/personal-icon.svg') }}" alt="">
                </div>
                <h2 class="bd-home__service-title">Pembuatan NPWP Pribadi</h2>
                <p class="bd-home__service-description">Buat NPWP pribadi dengan mudah untuk keperluan kerja, usha, atau administrasi lainnya.</p>
                <x-button href="{{ route('daftar') }}" variant="home">Daftar Sekarang</x-button>
            </article>

            <article class="bd-home__service-card">
                <div class="bd-home__business-icon" aria-hidden="true"><span></span></div>
                <h2 class="bd-home__service-title">Pembuatan NPWP badan</h2>
                <p class="bd-home__service-description">Layanan pembuatan NPWP untuk perusahaan, CV, PT, Yayasan, dan badan usaha lainnya.</p>
                <x-button href="{{ route('daftar') }}" variant="home">Daftar Sekarang</x-button>
            </article>

            <article class="bd-home__service-card">
                <div class="bd-home__service-icon bd-home__service-icon--tax">
                    <img src="{{ asset('images/figma/home/tax-icon.svg') }}" alt="">
                </div>
                <h2 class="bd-home__service-title">Pelaporan pajak</h2>
                <p class="bd-home__service-description">Bantu Laporan SPT Tahunan &amp;<br>SPT massa dengan benar, cepat,<br>dan tepat</p>
                <x-button variant="home" type="button" disabled aria-disabled="true">Lapor Sekarang</x-button>
            </article>
        </section>

        <section id="cara-mudah" class="bd-home__how" aria-labelledby="how-title">
            <div class="bd-home__section-heading">
                <p class="bd-home__section-kicker">CARA MUDAH</p>
                <h2 id="how-title" class="bd-home__section-title">Bagaimana cara Mendaftarkan?</h2>
            </div>

            <div class="bd-home__steps">
                <img class="bd-home__steps-image" src="{{ asset('images/figma/home/steps-flow.png') }}" alt="">
                <div class="bd-home__step-captions">
                    <div class="bd-home__step-caption"><strong>Pilih Layanan</strong>Pilih layanan yang kamu<br>butuhkan NPWP Pribadi,<br>NPWP Badan, atau<br>Lapor Pajak</div>
                    <div class="bd-home__step-caption"><strong>Upload Dokumen</strong>Lengkapi data diri atau<br>data perusahaan sesuai<br>formulir yang tersedia</div>
                    <div class="bd-home__step-caption"><strong>Upload Dokumen</strong>Unggah dokumen yang<br>diperlukan sesuai<br>ketentuan.</div>
                    <div class="bd-home__step-caption"><strong>Proses verifikasi</strong>Tim kami akan memeriksa<br>dan memverifikasi data<br>dan dokumen kamu.</div>
                    <div class="bd-home__step-caption"><strong>Pilih Layanan</strong>NPWP kamu terbit atau<br>lapor pajak kamu<br>berhasil dikirim.</div>
                </div>
            </div>

            <div class="bd-home__trust-card">
                <img src="{{ asset('images/figma/home/trust-card-icon.svg') }}" alt="">
                <p class="bd-home__trust-title">Aman, Cepat, dan terpercaya</p>
                <p class="bd-home__trust-copy">Data kamu aman bersama kami. Proses cepat tanpa keluar rumah.</p>
            </div>
        </section>

        <section id="testimoni" class="bd-home__testimonials" aria-labelledby="testimonial-title">
            <h2 id="testimonial-title">Testimoni Klien</h2>
            <p>Pendafat Klien yang telah menggunakan layanan&nbsp; Bantudaftarin</p>
            <div class="bd-home__testimonial-card">
                <blockquote>"Awalnya saya bingung cara daftar NPWP pribadi karena takut salah isi data. Untung ketemu Bantudaftarin. Prosesnya cepat, dijelaskan dengan sabar, dan saya tinggal kirim dokumen yang diperlukan. Dalam waktu singkat NPWP saya sudah selesai. Pelayanannya ramah dan sangat membantu."</blockquote>
                <p class="bd-home__testimonial-name">Andi Pratama</p>
            </div>
            <div class="bd-home__testimonial-controls" aria-label="Kontrol testimoni">
                <button type="button" aria-label="Testimoni sebelumnya"><img src="{{ asset('images/figma/home/arrow-left.svg') }}" alt=""></button>
                <button type="button" aria-label="Testimoni berikutnya"><img src="{{ asset('images/figma/home/arrow-right.svg') }}" alt=""></button>
            </div>
        </section>

        <section class="bd-home__cta-wrap" aria-label="Mulai layanan">
            <div class="bd-home__cta">
                <img src="{{ asset('images/figma/home/footer-icon.svg') }}" alt="">
                <h2>Urus Pajakmu Sekarang,<br>Lebih Mudah bersama Bantudaftarin!</h2>
                <x-button href="{{ route('daftar') }}" variant="home">Mulai Sekarang</x-button>
            </div>
        </section>
    </main>
</div>
@endsection
