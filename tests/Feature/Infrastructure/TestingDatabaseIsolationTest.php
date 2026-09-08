<?php

namespace Tests\Feature\Infrastructure;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TestingDatabaseIsolationTest extends TestCase
{
    public function test_feature_tests_use_the_dedicated_postgresql_database(): void
    {
        $this->assertTrue(app()->environment('testing'));
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame('bantu_daftarin_mvp_test', DB::connection()->getDatabaseName());
    }
}
