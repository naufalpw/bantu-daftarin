<?php

namespace Tests\Feature\Public;

use Tests\TestCase;

class LoginPagePolishTest extends TestCase
{
    public function test_public_login_keeps_real_auth_destinations_in_the_refined_layout(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Selamat Datang')
            ->assertSee('href="'.route('home').'"', false)
            ->assertSee('href="'.route('password.request').'"', false)
            ->assertSee('href="'.route('register').'"', false)
            ->assertSee('action="'.route('login.store').'"', false)
            ->assertSee(asset('images/figma/auth/login-illustration.svg'), false)
            ->assertDontSee(asset('images/figma/auth/login-illustration.png'), false)
            ->assertSee('autocomplete="email"', false)
            ->assertSee('autocomplete="current-password"', false);
    }

    public function test_public_login_has_no_unsupported_social_or_marketing_controls(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Selamat datang kembali')
            ->assertDontSee('Akses ruang pengajuan')
            ->assertDontSee('Google')
            ->assertDontSee('Facebook')
            ->assertDontSee('Live Chat')
            ->assertDontSee('Keranjang');
    }
}
