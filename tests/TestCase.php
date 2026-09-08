<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use LogicException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $connection = DB::connection();

        if (! app()->environment('testing')
            || $connection->getDriverName() !== 'pgsql'
            || $connection->getDatabaseName() !== 'bantu_daftarin_mvp_test') {
            throw new LogicException('Feature tests must use the dedicated PostgreSQL testing database.');
        }
    }
}
