<?php

namespace Tests\Feature\Security;

use App\Services\ProductionSecurityCheck;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ProductionSecurityCheckTest extends TestCase
{
    public function test_safe_effective_production_configuration_has_no_failures(): void
    {
        $this->configureSafeProduction();

        $checks = app(ProductionSecurityCheck::class)->run();

        $this->assertNotContains('FAIL', array_column($checks, 'status'));
        $this->assertSame('NOT_VERIFIABLE', collect($checks)->firstWhere('name', 'hsts')['status']);
        $this->assertSame('NOT_VERIFIABLE', collect($checks)->firstWhere('name', 'tls_terminator')['status']);
    }

    public function test_unsafe_production_debug_log_mailer_and_testing_scanner_fail_the_command(): void
    {
        $this->configureSafeProduction();
        config()->set([
            'app.debug' => true,
            'mail.default' => 'log',
            'files.malware_scan_driver' => 'testing',
        ]);

        $exitCode = Artisan::call('security:check-production');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('debug_mode', $output);
        $this->assertStringContainsString('mail_transport', $output);
        $this->assertStringContainsString('malware_driver', $output);
    }

    public function test_log_mailer_inside_failover_is_rejected(): void
    {
        $this->configureSafeProduction();
        config()->set('mail.default', 'failover');

        $check = collect(app(ProductionSecurityCheck::class)->run())->firstWhere('name', 'mail_transport');

        $this->assertSame('FAIL', $check['status']);
    }

    public function test_command_output_never_prints_configured_secret_values(): void
    {
        $this->configureSafeProduction();
        config()->set([
            'app.key' => 'base64:synthetic-app-key-must-not-print',
            'mail.mailers.smtp.password' => 'synthetic-smtp-password-must-not-print',
            'services.xendit.secret_key' => 'synthetic-xendit-secret-must-not-print',
            'services.xendit.callback_token' => 'synthetic-callback-token-must-not-print',
        ]);

        Artisan::call('security:check-production');
        $output = Artisan::output();

        $this->assertStringNotContainsString('synthetic-app-key-must-not-print', $output);
        $this->assertStringNotContainsString('synthetic-smtp-password-must-not-print', $output);
        $this->assertStringNotContainsString('synthetic-xendit-secret-must-not-print', $output);
        $this->assertStringNotContainsString('synthetic-callback-token-must-not-print', $output);
    }

    public function test_missing_production_secrets_and_unsafe_storage_fail_closed(): void
    {
        $this->configureSafeProduction();
        config()->set([
            'app.key' => '',
            'filesystems.default' => 'public',
            'filesystems.disks.private.serve' => true,
            'services.xendit.secret_key' => '',
            'services.xendit.callback_token' => '',
        ]);

        $failed = collect(app(ProductionSecurityCheck::class)->run())
            ->where('status', 'FAIL')
            ->pluck('name')
            ->all();

        $this->assertContains('application_key', $failed);
        $this->assertContains('filesystem_default', $failed);
        $this->assertContains('private_storage', $failed);
        $this->assertContains('xendit_secret', $failed);
        $this->assertContains('xendit_callback_token', $failed);
    }

    public function test_debug_level_inside_the_active_log_stack_is_rejected(): void
    {
        $this->configureSafeProduction();
        config()->set([
            'logging.default' => 'stack',
            'logging.channels.stack.channels' => ['single'],
            'logging.channels.single.level' => 'debug',
        ]);

        $check = collect(app(ProductionSecurityCheck::class)->run())->firstWhere('name', 'log_level');

        $this->assertSame('FAIL', $check['status']);
    }

    private function configureSafeProduction(): void
    {
        config()->set([
            'app.env' => 'production',
            'app.debug' => false,
            'app.url' => 'https://bantudaftarin.example.id',
            'app.key' => 'base64:synthetic-present-key',
            'session.driver' => 'database',
            'session.secure' => true,
            'session.http_only' => true,
            'session.same_site' => 'lax',
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.example.id',
            'mail.mailers.smtp.port' => 587,
            'mail.mailers.smtp.username' => 'synthetic-user',
            'mail.mailers.smtp.password' => 'synthetic-password',
            'mail.from.address' => 'no-reply@bantudaftarin.id',
            'queue.default' => 'database',
            'filesystems.default' => 'private',
            'filesystems.disks.private.driver' => 'local',
            'filesystems.disks.private.root' => storage_path('app/private'),
            'filesystems.disks.private.serve' => false,
            'filesystems.disks.quarantine.driver' => 'local',
            'filesystems.disks.quarantine.root' => storage_path('app/quarantine'),
            'filesystems.disks.quarantine.serve' => false,
            'files.malware_scan_driver' => 'clamav',
            'files.clamav_signature_max_age_hours' => 48,
            'files.retention_days' => 365,
            'services.xendit.driver' => 'xendit',
            'services.xendit.secret_key' => 'synthetic-xendit-secret',
            'services.xendit.callback_token' => 'synthetic-callback-token',
            'database.default' => 'pgsql',
            'logging.default' => 'single',
            'logging.channels.single.level' => 'info',
        ]);
    }
}
