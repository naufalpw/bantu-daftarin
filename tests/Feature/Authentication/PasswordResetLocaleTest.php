<?php

namespace Tests\Feature\Authentication;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class PasswordResetLocaleTest extends TestCase
{
    public function test_password_broker_statuses_have_project_owned_indonesian_translations(): void
    {
        Config::set('app.locale', 'id');

        $this->assertSame('Kata sandi Anda sudah diubah.', Lang::get('passwords.reset'));
        $this->assertSame('Tautan untuk mengatur ulang kata sandi sudah dikirim ke email Anda.', Lang::get('passwords.sent'));
        $this->assertSame('Tunggu sebentar sebelum mencoba lagi.', Lang::get('passwords.throttled'));
        $this->assertSame('Tautan pengaturan ulang kata sandi tidak valid atau sudah kedaluwarsa.', Lang::get('passwords.token'));
        $this->assertSame('Kami tidak menemukan akun dengan alamat email tersebut.', Lang::get('passwords.user'));
    }
}
