@extends('layouts.marketing')

@section('body_class', 'bd-phase3-body bd-qna-body')

@section('content')
    <x-site-header variant="payment" />

    <main class="bd-phase3-main bd-qna-page" data-node-id="219:11171">
        <a class="bd-phase3-back" href="{{ route('home') }}">
            <img src="{{ asset('images/figma/register/back.svg') }}" alt="">
            <span>Kembali</span>
        </a>

        <h1 class="bd-qna-title">QnA</h1>

        @php($questions = [
            [
                'question' => 'Apa itu Bantudaftarin?',
                'answer' => 'Bantudaftarin adalah layanan yang membantu proses administrasi perpajakan secara online, mulai dari pembuatan NPWP pribadi, NPWP badan usaha, hingga pelaporan pajak. Proses dilakukan dengan mudah, cepat, dan didampingi oleh tim yang berpengalaman.',
            ],
            [
                'question' => 'Dokumen apa saja yang diperlukan untuk membuat NPWP?',
                'answer' => 'Dokumen yang dibutuhkan bergantung pada jenis layanan yang dipilih. Umumnya Anda perlu menyiapkan KTP, NPWP lama (jika ada), serta dokumen pendukung seperti akta notaris dan SK AHU untuk badan usaha. Daftar persyaratan akan ditampilkan sebelum proses pendaftaran dimulai.',
            ],
            ['question' => 'Berapa lama proses pembuatan NPWP?', 'answer' => null],
            ['question' => 'Apakah data dan dokumen saya aman?', 'answer' => null],
            ['question' => 'Bagaimana jika saya mengalami kendala saat proses pendaftaran?', 'answer' => null],
        ])

        <section class="bd-qna-layout" data-qna>
            <div class="bd-qna-question-list" role="list" aria-label="Pertanyaan umum">
                @foreach($questions as $index => $item)
                    <div role="listitem">
                        <button
                            class="bd-qna-question"
                            type="button"
                            data-qna-question
                            data-active="{{ $index === 0 ? 'true' : 'false' }}"
                            data-answer-title="{{ $item['question'] }}"
                            data-answer-body="{{ $item['answer'] ?? '' }}"
                            aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                            aria-controls="qna-answer"
                        >
                            <span class="bd-qna-question__copy">
                                <span class="bd-qna-question__bullet" aria-hidden="true">
                                    <img class="bd-qna-question__bullet--active" src="{{ asset('images/figma/phase3/qna/bullet-active.svg') }}" alt="">
                                    <img class="bd-qna-question__bullet--inactive" src="{{ asset('images/figma/phase3/qna/bullet-inactive.svg') }}" alt="">
                                </span>
                                <span>{{ $item['question'] }}</span>
                            </span>
                            <span class="bd-qna-question__chevron" aria-hidden="true">
                                <img class="bd-qna-question__chevron--active" src="{{ asset('images/figma/phase3/qna/chevron-active.svg') }}" alt="">
                                <img class="bd-qna-question__chevron--inactive" src="{{ asset('images/figma/phase3/qna/chevron-inactive.svg') }}" alt="">
                            </span>
                        </button>
                    </div>
                @endforeach
            </div>

            <article id="qna-answer" class="bd-qna-answer" aria-live="polite">
                <h2 data-qna-answer-title>{{ $questions[0]['question'] }}</h2>
                <p data-qna-answer-body>{{ $questions[0]['answer'] }}</p>
            </article>
        </section>
    </main>
@endsection
