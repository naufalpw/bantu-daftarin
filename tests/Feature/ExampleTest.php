<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_shows_home_to_guests(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('BantuUrusanPajakJadiMudah');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
    }
}
