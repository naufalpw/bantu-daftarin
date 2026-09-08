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
        $response->assertSee('Urusan NPWP jadi lebih terarah');
        $response->assertDontSee('pb-home-hero__note', false);
        $response->assertDontSee('100% online');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
    }
}
