<?php

namespace App\Support;

use Illuminate\Support\Str;

final class HelpFaq
{
    /**
     * @return list<array{id: string, label: string}>
     */
    public static function categories(): array
    {
        return [
            ['id' => 'all', 'label' => 'Semua'],
            ['id' => 'account', 'label' => 'Akun'],
            ['id' => 'documents', 'label' => 'Data & Dokumen'],
            ['id' => 'payments', 'label' => 'Pembayaran'],
            ['id' => 'process', 'label' => 'Proses'],
            ['id' => 'results', 'label' => 'Hasil'],
            ['id' => 'privacy-support', 'label' => 'Privasi & Bantuan'],
        ];
    }

    /**
     * @return list<array{id: string, category: string, question: string, answer: string, keywords: list<string>}>
     */
    public static function entries(): array
    {
        return [
            self::item('tentang-bantudaftarin', 'account', 'Apa itu BantuDaftarin?', 'BantuDaftarin adalah layanan bantuan administrasi untuk menyiapkan pengajuan NPWP Perseorangan dan NPWP Badan Usaha. Anda dapat melengkapi data dan dokumen, memantau status pengajuan, serta mengakses hasil setelah proses selesai dan terverifikasi. BantuDaftarin bukan portal resmi pemerintah.', ['layanan', 'npwp', 'portal pemerintah']),
            self::item('membuat-akun', 'account', 'Bagaimana cara membuat akun?', 'Buka halaman pendaftaran, lalu isi nama lengkap, email, dan kata sandi. Setelah mendaftar, selesaikan verifikasi email sebelum masuk dan membuat pengajuan.', ['daftar', 'registrasi', 'akun baru']),
            self::item('verifikasi-email', 'account', 'Mengapa saya perlu memverifikasi email?', 'Verifikasi memastikan alamat email dapat digunakan untuk menerima informasi akun dan melanjutkan proses masuk. Pengajuan baru dapat dibuat setelah email terverifikasi.', ['email belum terverifikasi', 'tautan verifikasi']),
            self::item('email-verifikasi-tidak-diterima', 'account', 'Saya tidak menerima email verifikasi, apa yang harus dilakukan?', 'Periksa folder spam dan pastikan alamat email yang didaftarkan benar. Jika email belum diterima, gunakan tindakan kirim ulang verifikasi yang tersedia dan tunggu masa jeda sebelum mencoba kembali.', ['kirim ulang', 'spam', 'resend', 'verifikasi tidak masuk']),
            self::item('otp-login', 'account', 'Mengapa saya diminta kode OTP saat masuk?', 'Kode OTP merupakan langkah verifikasi tambahan setelah email dan kata sandi benar. Masukkan kode yang dikirim ke email Anda sebelum masa berlakunya berakhir.', ['kode masuk', 'login', 'kode otp']),
            self::item('lupa-kata-sandi', 'account', 'Apa yang harus dilakukan jika lupa kata sandi?', 'Gunakan tautan Lupa kata sandi di halaman masuk. Ikuti tautan pengaturan ulang yang dikirim ke email terdaftar untuk membuat kata sandi baru.', ['reset password', 'password', 'tidak bisa masuk']),

            self::item('dokumen-pengajuan', 'documents', 'Dokumen apa yang perlu disiapkan?', 'Dokumen bergantung pada layanan yang dipilih. Untuk NPWP Perseorangan, persyaratan dapat mencakup KTP, Kartu Keluarga, dan foto wajah. Untuk NPWP Badan Usaha, persyaratan dapat mencakup KTP penanggung jawab, akta notaris, dan SK AHU; Surat Kuasa diperlukan bila diwakilkan. Daftar lengkap tampil di ruang pengajuan.', ['ktp', 'kk', 'kartu keluarga', 'akta', 'sk ahu', 'syarat']),
            self::item('npwp-lama-opsional', 'documents', 'Apakah NPWP lama wajib diunggah?', 'Pada pengajuan NPWP Perseorangan, dokumen NPWP lama bersifat opsional dan dapat diunggah jika tersedia. Dokumen wajib tetap mengikuti daftar persyaratan pada ruang pengajuan Anda.', ['npwp jika ada', 'dokumen opsional', 'npwp lama']),
            self::item('format-berkas', 'documents', 'Format file apa yang dapat diunggah?', 'Persyaratan dokumen saat ini menerima JPG, JPEG, PNG, atau PDF. Jenis dan ukuran maksimum yang berlaku ditampilkan pada setiap kontrol unggah karena batasnya dapat berbeda, mulai dari 5 MB hingga 15 MB.', ['mime', 'pdf', 'png', 'jpg', 'jpeg', 'ukuran file', 'maksimal']),
            self::item('foto-wajah', 'documents', 'Apakah Foto Wajah harus menggunakan kamera?', 'Tidak. Anda dapat mengambil foto melalui kamera perangkat atau memilih file foto yang sudah tersedia. Keduanya masuk ke persyaratan Foto Wajah yang sama.', ['kamera', 'unggah foto', 'selfie', 'file wajah']),
            self::item('ganti-dokumen', 'documents', 'Bisakah saya mengganti dokumen yang sudah diunggah?', 'Dokumen dapat diganti selama tahap pengajuan dan aturan dokumen masih mengizinkan perubahan. Jika tindakan Ganti dokumen tersedia pada kartu dokumen, gunakan tindakan tersebut untuk mengunggah versi baru.', ['replace', 'upload ulang', 'versi dokumen', 'ubah dokumen']),
            self::item('revisi-dokumen', 'documents', 'Bagaimana jika dokumen saya perlu diperbaiki?', 'Alasan dan instruksi perbaikan ditampilkan pada dokumen terkait. Unggah dokumen pengganti sesuai instruksi, lalu kirim kembali perbaikan melalui ruang pengajuan.', ['revisi', 'ditolak', 'upload ulang', 'ganti dokumen', 'dokumen salah']),

            self::item('waktu-pembayaran', 'payments', 'Kapan saya dapat melakukan pembayaran?', 'Pembayaran tersedia setelah data dan seluruh dokumen wajib lengkap sesuai tahap pengajuan. Ruang pengajuan akan menampilkan tindakan pembayaran ketika tahap tersebut sudah dapat dilanjutkan.', ['bayar', 'dokumen lengkap', 'tagihan']),
            self::item('metode-pembayaran', 'payments', 'Metode pembayaran apa yang tersedia?', 'Pilihan yang tersedia saat ini adalah BCA, BRI, dan QRIS. PayPal belum tersedia. Instruksi pembayaran yang berlaku ditampilkan setelah Anda memilih metode pada halaman pembayaran.', ['bank', 'virtual account', 'qris', 'bca', 'bri', 'paypal']),
            self::item('pembayaran-menunggu', 'payments', 'Mengapa pembayaran saya masih menunggu?', 'Status menunggu berarti pembayaran belum dikonfirmasi oleh alur pembayaran yang digunakan. Periksa kembali instruksi dan status pada halaman pembayaran sebelum membuat pembayaran baru.', ['pending', 'belum terkonfirmasi', 'menunggu bayar']),
            self::item('pembayaran-kedaluwarsa', 'payments', 'Apa yang terjadi jika pembayaran kedaluwarsa?', 'Instruksi pembayaran yang sudah kedaluwarsa tidak dapat digunakan kembali. Buka ruang pengajuan untuk melihat status terbaru dan buat instruksi pembayaran baru jika tindakan tersebut tersedia.', ['expired', 'kedaluwarsa', 'ulang pembayaran']),

            self::item('perkembangan-pengajuan', 'process', 'Bagaimana cara melihat perkembangan pengajuan?', 'Buka menu Pengajuan lalu pilih pengajuan yang ingin dilihat. Status, langkah berikutnya, dan riwayat yang relevan ditampilkan pada ruang pengajuan.', ['tracking', 'status', 'riwayat', 'pantau']),
            self::item('durasi-pengajuan', 'process', 'Berapa lama proses pengajuan NPWP?', 'Waktu proses bergantung pada kelengkapan dokumen, pemeriksaan, dan proses pada instansi terkait. Perkembangan pengajuan dapat dipantau di ruang pengajuan. Perkiraan jadwal ditampilkan bila tersedia.', ['estimasi', 'berapa hari', 'lama proses', 'jadwal']),
            self::item('revisi-pengajuan', 'process', 'Apa yang harus dilakukan jika pengajuan membutuhkan revisi?', 'Periksa status dan petunjuk revisi di ruang pengajuan. Lengkapi perubahan yang diminta pada dokumen terkait, lalu kirim kembali perbaikan agar dapat diperiksa ulang.', ['perlu perbaikan', 'revisi pengajuan', 'kirim ulang']),

            self::item('hasil-tersedia', 'results', 'Kapan hasil dapat dilihat?', 'Hasil dapat dilihat setelah dokumen hasil selesai diverifikasi dan status pengajuan menyatakan hasil tersedia. Sebelum itu, tindakan lihat dan unduh tidak ditampilkan kepada klien.', ['hasil selesai', 'verifikasi hasil', 'dokumen hasil']),
            self::item('hasil-belum-tersedia', 'results', 'Mengapa hasil saya belum tersedia?', 'Hasil mungkin masih menunggu proses instansi, sedang disiapkan, atau sedang diverifikasi oleh tim. Lihat status terbaru pada ruang pengajuan untuk mengetahui tahap yang sedang berjalan.', ['belum ada hasil', 'hasil pending', 'proses eksternal']),
            self::item('unduh-hasil', 'results', 'Bagaimana cara mengunduh hasil?', 'Saat hasil terverifikasi tersedia, buka bagian Hasil pada ruang pengajuan lalu gunakan tindakan Unduh. Akses tetap dibatasi kepada pemilik pengajuan dan admin yang berwenang.', ['download', 'simpan hasil', 'lihat hasil']),

            self::item('penyimpanan-data', 'privacy-support', 'Bagaimana data dan dokumen saya disimpan?', 'Dokumen pengajuan disimpan melalui penyimpanan privat. Akses dibatasi sesuai otorisasi pengguna, dan dokumen tidak dibuka melalui tautan publik permanen. Pengguna lain tidak dapat mengakses dokumen pengajuan Anda melalui akun mereka.', ['privasi', 'private storage', 'aman', 'akses dokumen', 'pengguna lain']),
            self::item('bantuan-pengajuan', 'privacy-support', 'Bagaimana jika saya mengalami kendala saat proses pengajuan?', 'Untuk kendala yang terkait satu pengajuan, gunakan Tanya tentang pengajuan ini agar percakapan tetap memiliki konteks yang tepat. Jika masalah tidak terkait pengajuan atau jawabannya belum tersedia di Pusat Bantuan, gunakan Hubungi Admin untuk membuka Bantuan Umum.', ['chat', 'admin', 'kendala', 'bantuan umum', 'pertanyaan tidak tersedia']),
        ];
    }

    /**
     * @return list<array{id: string, category: string, question: string, answer: string, keywords: list<string>}>
     */
    public static function landingPreview(): array
    {
        $ids = [
            'tentang-bantudaftarin',
            'dokumen-pengajuan',
            'durasi-pengajuan',
            'penyimpanan-data',
            'bantuan-pengajuan',
        ];

        return array_values(array_filter(
            self::entries(),
            static fn (array $entry): bool => in_array($entry['id'], $ids, true),
        ));
    }

    /**
     * @return list<array{id: string, category: string, question: string, answer: string, keywords: list<string>}>
     */
    public static function filter(string $query = '', string $category = 'all'): array
    {
        $terms = collect(preg_split('/\s+/u', Str::lower(trim($query))) ?: [])->filter()->values();

        return array_values(array_filter(self::entries(), static function (array $entry) use ($category, $terms): bool {
            if ($category !== 'all' && $entry['category'] !== $category) {
                return false;
            }

            $haystack = Str::lower(implode(' ', [
                $entry['question'],
                $entry['answer'],
                self::categoryLabel($entry['category']),
                ...$entry['keywords'],
            ]));

            return $terms->every(static fn (string $term): bool => str_contains($haystack, $term));
        }));
    }

    public static function categoryLabel(string $category): string
    {
        foreach (self::categories() as $item) {
            if ($item['id'] === $category) {
                return $item['label'];
            }
        }

        return $category;
    }

    /**
     * @param  list<string>  $keywords
     * @return array{id: string, category: string, question: string, answer: string, keywords: list<string>}
     */
    private static function item(string $id, string $category, string $question, string $answer, array $keywords): array
    {
        return compact('id', 'category', 'question', 'answer', 'keywords');
    }
}
