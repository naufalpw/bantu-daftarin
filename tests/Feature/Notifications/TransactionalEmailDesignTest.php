<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\ApplicationUpdateNotification;
use App\Notifications\ChatUnreadNotification;
use App\Notifications\LoginOtpNotification;
use App\Notifications\PasswordResetNotification;
use App\Notifications\ResultAvailableNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class TransactionalEmailDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_transactional_notifications_use_the_shared_html_and_plain_text_shell(): void
    {
        $user = User::factory()->create(['name' => 'Naufal', 'email' => 'naufal@example.test']);
        $applicationId = '00000000-0000-4000-8000-0000002646ab';
        $notifications = [
            new LoginOtpNotification('482731'),
            new VerifyEmailNotification,
            new PasswordResetNotification('reset-token-should-not-appear-in-preheader'),
            new ChatUnreadNotification('00000000-0000-4000-8000-000000000001', 'NPWP Badan Usaha · ID …2646AB'),
            new ApplicationUpdateNotification(
                'Status pengajuan Anda diperbarui',
                'Status pengajuan Anda sekarang: Perlu perbaikan dokumen.',
                $applicationId,
                'NPWP Perseorangan',
                'Status pengajuan diperbarui',
                'Ada pembaruan pada pengajuan Anda.',
                'Status terbaru',
                'Perlu perbaikan dokumen',
            ),
            new ResultAvailableNotification($applicationId, 'result-public-id', 'NPWP Perseorangan'),
        ];

        foreach ($notifications as $notification) {
            $message = $notification->toMail($user);
            $html = (string) $message->render();
            $plainText = $this->renderPlainText($message);

            $this->assertStringContainsString('BantuDaftarin', $html);
            $this->assertStringContainsString('color-scheme', $html);
            $this->assertStringContainsString('Email transaksi otomatis dari BantuDaftarin.', $html);
            $this->assertStringContainsString($message->viewData['title'], $plainText);
            $this->assertStringContainsString('Email transaksi otomatis dari BantuDaftarin.', $plainText);
            $this->assertStringNotContainsString('3173055501010001', $html);
            $this->assertStringNotContainsString('3173055501010002', $html);
            $this->assertStringNotContainsString('applications/private/', $html);
            $this->assertStringNotContainsString('Isi pesan rahasia', $html);
        }
    }

    public function test_subjects_preheaders_and_critical_content_are_consistent_and_safe(): void
    {
        $user = User::factory()->create(['name' => 'Naufal']);
        $applicationId = '00000000-0000-4000-8000-0000002646ab';
        $otp = new LoginOtpNotification('482731');
        $verification = new VerifyEmailNotification;
        $reset = new PasswordResetNotification('reset-token-should-not-appear-in-preheader');
        $chat = new ChatUnreadNotification('00000000-0000-4000-8000-000000000001', 'Bantuan Umum');
        $status = new ApplicationUpdateNotification('Status pengajuan Anda diperbarui', 'Status pengajuan Anda sekarang: Sedang diproses.', $applicationId, 'NPWP Perseorangan', 'Status pengajuan diperbarui', 'Ada pembaruan pada pengajuan Anda.', 'Status terbaru', 'Sedang diproses');
        $estimate = new ApplicationUpdateNotification('Estimasi pengajuan Anda diperbarui', 'Estimasi penyelesaian pengajuan Anda telah diperbarui.', $applicationId, 'NPWP Perseorangan', 'Estimasi pengajuan diperbarui', 'Estimasi penyelesaian pengajuan Anda telah diperbarui.', 'Estimasi', '10 Sep 2026, 10:00 WIB');
        $payment = new ApplicationUpdateNotification('Pembayaran pengajuan telah diterima', 'Pembayaran pengajuan Anda telah diterima.', $applicationId, 'NPWP Perseorangan', 'Pembayaran telah diterima', 'Pembayaran pengajuan Anda telah diterima.', 'Jumlah', 'IDR 500.000');
        $result = new ResultAvailableNotification($applicationId, 'result-public-id', 'NPWP Perseorangan');

        $expected = [
            [$otp, 'Kode verifikasi BantuDaftarin', 'Gunakan kode verifikasi untuk melanjutkan masuk ke akun BantuDaftarin Anda.'],
            [$verification, 'Verifikasi email BantuDaftarin', 'Selesaikan verifikasi email akun BantuDaftarin Anda.'],
            [$reset, 'Atur ulang kata sandi BantuDaftarin', 'Gunakan tautan yang tersedia untuk mengatur ulang kata sandi akun Anda.'],
            [$chat, 'Ada pesan baru di BantuDaftarin', 'Anda memiliki pesan yang belum dibaca di BantuDaftarin.'],
            [$status, 'Status pengajuan Anda diperbarui', 'Ada pembaruan pada pengajuan Anda.'],
            [$estimate, 'Estimasi pengajuan Anda diperbarui', 'Estimasi penyelesaian pengajuan Anda telah diperbarui.'],
            [$payment, 'Pembayaran pengajuan telah diterima', 'Pembayaran pengajuan Anda telah diterima.'],
            [$result, 'Hasil pengajuan Anda sudah tersedia', 'Hasil pengajuan Anda sudah tersedia untuk ditinjau.'],
        ];

        foreach ($expected as [$notification, $subject, $preheader]) {
            $message = $notification->toMail($user);

            $this->assertSame($subject, $message->subject);
            $this->assertSame($preheader, $message->viewData['preheader']);
            $this->assertStringNotContainsString('482731', $message->viewData['preheader']);
            $this->assertStringNotContainsString('reset-token-should-not-appear-in-preheader', $message->viewData['preheader']);
            $this->assertStringNotContainsString('signature=', $message->viewData['preheader']);
        }

        $otpMessage = $otp->toMail($user);
        $otpHtml = (string) $otpMessage->render();
        $otpPlainText = $this->renderPlainText($otpMessage);
        $this->assertStringContainsString('482731', $otpHtml);
        $this->assertStringNotContainsString('482 731', $otpHtml);
        $this->assertStringContainsString('482731', $otpPlainText);
        $this->assertStringNotContainsString('482 731', $otpPlainText);

        $this->assertSame('Buka percakapan', $chat->toMail($user)->actionText);
        $this->assertSame('Buka pengajuan', $status->toMail($user)->actionText);
        $this->assertSame('Lihat hasil pengajuan', $result->toMail($user)->actionText);
    }

    public function test_the_email_header_uses_the_full_wordmark_without_square_distortion_or_duplicate_text(): void
    {
        $template = file_get_contents(resource_path('views/mail/transactional.blade.php'));

        $this->assertNotFalse($template);
        $this->assertStringContainsString('logo-color.png', $template);
        $this->assertStringContainsString('alt="BantuDaftarin"', $template);
        $this->assertStringContainsString('width="160"', $template);
        $this->assertStringContainsString('width:160px;max-width:100%;height:auto', $template);
        $this->assertStringNotContainsString('height="38"', $template);
        $this->assertStringNotContainsString('width:38px;height:38px', $template);
        $this->assertStringNotContainsString('>BantuDaftarin</td>', $template);
    }

    private function renderPlainText(MailMessage $message): string
    {
        return View::make('mail.transactional-text', $message->data())->render();
    }
}
