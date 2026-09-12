<?php

namespace App\Console\Commands;

use App\Services\ProductionSecurityCheck;
use Illuminate\Console\Command;

class CheckProductionSecurity extends Command
{
    protected $signature = 'security:check-production';

    protected $description = 'Validate redacted effective configuration against production security requirements.';

    public function handle(ProductionSecurityCheck $securityCheck): int
    {
        $checks = $securityCheck->run();

        $this->table(
            ['Check', 'Status', 'Detail'],
            array_map(
                fn (array $check): array => [$check['name'], $check['status'], $check['detail']],
                $checks,
            ),
        );

        $failures = count(array_filter($checks, fn (array $check): bool => $check['status'] === 'FAIL'));
        $this->line(sprintf('Summary: %d check(s), %d failure(s).', count($checks), $failures));

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
