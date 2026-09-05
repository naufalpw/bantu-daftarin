<?php

namespace Tests\Feature\Public;

use Tests\TestCase;

class RegisterPagePolishTest extends TestCase
{
    public function test_public_register_renders_the_real_registration_form_and_login_path(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Buat Akun Baru')
            ->assertSee('Form Pendaftaran')
            ->assertSee('Daftar dan kirim verifikasi')
            ->assertSee('Setelah mendaftar, verifikasi email Anda sebelum masuk.')
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee('action="'.route('register.store').'"', false)
            ->assertSee('autocomplete="name"', false)
            ->assertSee('autocomplete="new-password"', false);
    }

    public function test_daftar_remains_a_compatibility_redirect_to_register(): void
    {
        $this->get(route('daftar'))->assertRedirect(route('register'));
    }

    public function test_register_no_longer_renders_the_obsolete_promotional_panel_copy(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertDontSee('Buat akun untuk memulai')
            ->assertDontSee('pb-auth-visual', false);
    }
}
