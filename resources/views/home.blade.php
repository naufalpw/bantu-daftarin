@extends('layouts.marketing')

@section('body_class', 'pb-public-body')
@section('title', 'Bantu Daftarin | Bantuan administrasi NPWP')
@section('meta_description', 'Bantuan administrasi untuk menyiapkan pengajuan NPWP Perseorangan dan NPWP Badan Usaha.')

@section('content')
{{-- DEMO / PLACEHOLDER CONTENT: replace with verified testimonials before production. --}}
@php
    $demoTestimonials = [
        [
            'name' => 'Raka Pradana',
            'quote' => 'Alur pengajuannya mudah diikuti. Saya bisa melihat dokumen apa yang perlu dilengkapi dan mengetahui tahapan proses tanpa harus mencari informasi di banyak halaman.',
        ],
        [
            'name' => 'Siti Maharani',
            'quote' => 'Bagian status pengajuan membantu saya memahami apa yang sedang diproses dan kapan saya perlu melakukan tindakan berikutnya.',
        ],
        [
            'name' => 'Dimas Kurniawan',
            'quote' => 'Formulir dan daftar dokumen tersusun dengan jelas. Ketika ada bagian yang perlu diperbaiki, informasinya mudah ditemukan.',
        ],
        [
            'name' => 'Nadia Prameswari',
            'quote' => 'Saya terbantu karena data, dokumen, pembayaran, dan perkembangan pengajuan dapat dipantau dari ruang pengajuan yang sama.',
        ],
    ];
    $qnaEntries = \App\Support\PublicQna::entries();
@endphp
<x-site-header />
<main id="main-content" class="pb-home" tabindex="-1">
    <section id="beranda" class="pb-home-hero" aria-labelledby="home-title">
        <div class="pb-home-hero__copy">
            <p class="pb-home-hero__tag">#BantuUrusanPajakJadiMudah</p>
            <img class="pb-home-hero__wordmark" src="{{ asset('images/figma/home/logo-color.png') }}" alt="Bantu Daftarin">
            <h1 id="home-title">Urusan NPWP jadi lebih terarah</h1>
            <p class="pb-home-hero__description">Siapkan pengajuan NPWP Perseorangan atau Badan Usaha, mulai dari data dan dokumen hingga melihat perkembangan proses.</p>
            <div class="pb-home-hero__actions">
                <a class="pb-button pb-button--primary" href="{{ route('register') }}">Daftar untuk mulai</a>
                <a class="pb-button pb-button--secondary" href="#layanan">Lihat layanan</a>
            </div>
        </div>
        <div class="pb-home-hero__art">
            <img src="{{ asset('images/figma/home/hero-image.png') }}" alt="Ilustrasi layanan administrasi NPWP Bantu Daftarin" fetchpriority="high" decoding="async">
        </div>
        <ul class="pb-home-hero__benefits" aria-label="Bantuan pengajuan">
            <li>
                <span class="pb-home-hero__benefit-icon"><img src="{{ asset('images/figma/home/trust-icon.svg') }}" alt="" aria-hidden="true" loading="lazy"></span>
                <span>Data &amp; Dokumen<br>Terstruktur</span>
            </li>
            <li>
                <span class="pb-home-hero__benefit-icon"><img src="{{ asset('images/figma/home/process-icon.svg') }}" alt="" aria-hidden="true" loading="lazy"></span>
                <span>Pantau Status<br>Pengajuan</span>
            </li>
            <li>
                <span class="pb-home-hero__benefit-icon"><img src="{{ asset('images/figma/home/support-icon.svg') }}" alt="" aria-hidden="true" loading="lazy"></span>
                <span>Bantuan Sesuai<br>Pengajuan</span>
            </li>
        </ul>
    </section>

    <section id="layanan" class="pb-home-services" aria-labelledby="home-services-title">
        <div class="pb-home-section-heading">
            <div>
                <p class="pb-kicker">Layanan</p>
                <h2 id="home-services-title">Pilih layanan pengajuan</h2>
            </div>
        </div>

        <div class="pb-home-service-options">
            <article class="pb-service-preview pb-service-preview--personal">
                <span class="pb-service-preview__icon"><img src="{{ asset('images/figma/home/personal-icon.svg') }}" alt="" aria-hidden="true" loading="lazy"></span>
                <div class="pb-service-preview__content">
                    <p class="pb-service-preview__context">Untuk kebutuhan pribadi</p>
                    <h3>NPWP Perseorangan</h3>
                    <p>Bantuan administratif untuk menyiapkan pengajuan NPWP perseorangan.</p>
                </div>
                <a class="pb-button pb-button--primary" href="{{ route('register') }}">Daftar untuk mulai</a>
            </article>

            <article class="pb-service-preview pb-service-preview--business">
                <span class="pb-service-preview__icon pb-service-preview__icon--business" aria-hidden="true">
                    <img src="{{ asset('images/figma/home/business-icon.svg') }}" alt="" loading="lazy">
                </span>
                <div class="pb-service-preview__content">
                    <p class="pb-service-preview__context">Untuk badan usaha</p>
                    <h3>NPWP Badan Usaha</h3>
                    <p>Bantuan administratif untuk badan usaha dan penanggung jawab utamanya.</p>
                </div>
                <a class="pb-button pb-button--primary" href="{{ route('register') }}">Daftar untuk mulai</a>
            </article>

            <article class="pb-service-preview pb-service-preview--coming-soon" aria-label="Lapor Pajak segera hadir">
                <span class="pb-service-preview__icon pb-service-preview__icon--tax" aria-hidden="true">
                    <img src="{{ asset('images/figma/home/tax-icon.png') }}" alt="" loading="lazy">
                </span>
                <div class="pb-service-preview__content">
                    <h3>Lapor Pajak</h3>
                    <p>Layanan pelaporan pajak sedang dipersiapkan.</p>
                </div>
                <p class="pb-service-preview__availability">Segera hadir</p>
            </article>
        </div>
    </section>

    <section id="cara-kerja" class="pb-assisted-process" aria-labelledby="assisted-process-title">
        <header class="pb-assisted-process__heading">
            <p class="pb-kicker">Cara mudah</p>
            <h2 id="assisted-process-title">Bagaimana cara mendaftarkan?</h2>
        </header>

        <div class="pb-assisted-process__flow">
            <figure class="pb-assisted-process__visual" aria-hidden="true">
                <img src="{{ asset('images/figma/home/steps-flow.png') }}" alt="" loading="lazy" decoding="async">
            </figure>

            <ol class="pb-assisted-process__steps">
                <li>
                    <h3>Pilih layanan</h3>
                    <p>Pilih layanan NPWP yang sesuai dengan kebutuhan Anda.</p>
                </li>
                <li>
                    <h3>Lengkapi data</h3>
                    <p>Isi data pribadi atau badan usaha sesuai formulir pengajuan.</p>
                </li>
                <li>
                    <h3>Unggah dokumen</h3>
                    <p>Lengkapi dokumen yang diperlukan sesuai jenis layanan.</p>
                </li>
                <li>
                    <h3>Pembayaran &amp; pemeriksaan</h3>
                    <p>Setelah dokumen lengkap, selesaikan pembayaran dan dokumen akan diperiksa.</p>
                </li>
                <li>
                    <h3>Pantau proses &amp; hasil</h3>
                    <p>Pantau perkembangan pengajuan dan akses hasil setelah terverifikasi.</p>
                </li>
            </ol>
        </div>

        <aside class="pb-assisted-process__note" aria-labelledby="assisted-process-note-title">
            <img src="{{ asset('images/figma/home/trust-card-icon.svg') }}" alt="" aria-hidden="true" loading="lazy">
            <div>
                <h3 id="assisted-process-note-title">Satu ruang untuk memantau pengajuan</h3>
                <p>Data, dokumen, pembayaran, perkembangan proses, dan hasil dapat Anda pantau dari ruang pengajuan sesuai tahap layanan.</p>
            </div>
        </aside>
    </section>

    <section class="pb-home-testimonials" aria-labelledby="home-testimonials-title">
        <header class="pb-home-testimonials__heading">
            <p class="pb-kicker">Testimoni</p>
            <h2 id="home-testimonials-title">Testimoni Klien</h2>
            <p>Contoh testimoni untuk tampilan demonstrasi.</p>
        </header>

        <div class="pb-testimonial-carousel" data-testimonial-carousel>
            <article class="pb-testimonial-card" aria-live="polite">
                <span class="pb-testimonial-card__quote" aria-hidden="true">“</span>
                <span class="pb-testimonial-card__accent" aria-hidden="true"></span>
                <blockquote>
                    <p data-testimonial-quote>{{ $demoTestimonials[0]['quote'] }}</p>
                    <footer>
                        <cite data-testimonial-author>{{ $demoTestimonials[0]['name'] }}</cite>
                        <span>Contoh pengguna</span>
                    </footer>
                </blockquote>
            </article>

            <div class="pb-testimonial-carousel__controls" aria-label="Navigasi testimoni">
                <button type="button" data-testimonial-previous aria-label="Testimoni sebelumnya">
                    <img src="{{ asset('images/figma/home/arrow-left.svg') }}" alt="" aria-hidden="true">
                </button>
                <span data-testimonial-index>1 / {{ count($demoTestimonials) }}</span>
                <button type="button" data-testimonial-next aria-label="Testimoni berikutnya">
                    <img src="{{ asset('images/figma/home/arrow-right.svg') }}" alt="" aria-hidden="true">
                </button>
            </div>

            <template data-testimonial-data>@json($demoTestimonials)</template>
        </div>
    </section>

    <section id="qna" class="pb-home-qna" aria-labelledby="home-qna-title">
        <div class="pb-home-qna__heading">
            <div>
                <p class="pb-kicker">Pertanyaan umum</p>
                <h2 id="home-qna-title">Pertanyaan sebelum memulai</h2>
            </div>
        </div>
        <div class="pb-home-qna__layout" data-home-qna>
            <div class="pb-home-qna__list" aria-label="Daftar pertanyaan">
                @foreach($qnaEntries as $index => $item)
                    <div class="pb-home-qna__item">
                        <button
                            type="button"
                            class="pb-home-qna__question"
                            data-home-qna-question
                            data-answer-title="{{ $item['question'] }}"
                            data-answer-body="{{ $item['answer'] }}"
                            data-active="{{ $index === 0 ? 'true' : 'false' }}"
                            aria-pressed="{{ $index === 0 ? 'true' : 'false' }}"
                            aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                        >
                            <span class="pb-home-qna__question-dot" aria-hidden="true"></span>
                            <span class="pb-home-qna__question-text">{{ $item['question'] }}</span>
                            <img src="{{ asset('images/figma/home/caret.svg') }}" alt="" aria-hidden="true">
                        </button>
                        <div class="pb-home-qna__mobile-answer" data-home-qna-mobile-answer @if($index !== 0) hidden @endif>
                            <p>{{ $item['answer'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <article class="pb-home-qna__answer" aria-live="polite">
                <h3 data-home-qna-answer-title>{{ $qnaEntries[0]['question'] }}</h3>
                <p data-home-qna-answer-body>{{ $qnaEntries[0]['answer'] }}</p>
            </article>
        </div>
    </section>

    <section class="pb-home-closing" aria-labelledby="home-closing-title">
        <div>
            <h2 id="home-closing-title">Siap memulai pengajuan?</h2>
            <p>Buat akun untuk melihat persyaratan NPWP Perseorangan atau Badan Usaha.</p>
        </div>
        <div class="pb-home-closing__actions">
            <a class="pb-button pb-button--light" href="{{ route('register') }}">Daftar untuk mulai</a>
            <a class="pb-button pb-button--light" href="{{ route('login') }}">Sudah punya akun? Masuk</a>
        </div>
    </section>

    <footer class="pb-public-footer">
        <div class="pb-public-footer__brand">
            <img src="{{ asset('images/figma/home/logo-color.png') }}" alt="Bantu Daftarin">
            <p>Layanan bantuan administrasi untuk NPWP Perseorangan dan NPWP Badan Usaha.</p>
        </div>
        <div class="pb-public-footer__links">
            <div><h2>Layanan</h2><a href="#layanan">NPWP Perseorangan</a><a href="#layanan">NPWP Badan Usaha</a></div>
            <div><h2>Akun</h2><a href="{{ route('login') }}">Masuk</a><a href="{{ route('register') }}">Daftar</a></div>
        </div>
        <small>&copy; {{ now()->year }} Bantu Daftarin</small>
    </footer>
</main>
@endsection
