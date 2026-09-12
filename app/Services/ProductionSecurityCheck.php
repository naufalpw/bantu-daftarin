<?php

namespace App\Services;

class ProductionSecurityCheck
{
    /**
     * @return list<array{name:string,status:string,detail:string}>
     */
    public function run(): array
    {
        $checks = [];

        $checks[] = $this->check('application_environment', config('app.env') === 'production', 'Production environment is selected.', 'APP_ENV must be production.');
        $checks[] = $this->check('debug_mode', config('app.debug') === false, 'Debug mode is disabled.', 'APP_DEBUG must be false.');
        $checks[] = $this->check('application_url', parse_url((string) config('app.url'), PHP_URL_SCHEME) === 'https', 'Application URL uses HTTPS.', 'APP_URL must use HTTPS.');
        $checks[] = $this->check('application_key', $this->present(config('app.key')), 'Application encryption key is present.', 'APP_KEY is missing.');

        $checks[] = $this->check('session_driver', ! in_array(config('session.driver'), ['array', 'cookie'], true), 'Session state uses a server-side driver.', 'Production sessions must use a server-side driver.');
        $checks[] = $this->check('session_secure', config('session.secure') === true, 'Session cookies require HTTPS.', 'SESSION_SECURE_COOKIE must be true.');
        $checks[] = $this->check('session_http_only', config('session.http_only') === true, 'Session cookies are HttpOnly.', 'SESSION_HTTP_ONLY must be true.');
        $checks[] = $this->check('session_same_site', in_array(config('session.same_site'), ['lax', 'strict'], true), 'Session SameSite policy is restrictive.', 'SESSION_SAME_SITE must be lax or strict.');

        $mailers = $this->selectedMailerTransports((string) config('mail.default'));
        $mailTransportSafe = $mailers !== [] && array_intersect($mailers, ['log', 'array']) === [];
        $checks[] = $this->check('mail_transport', $mailTransportSafe, 'Selected mail transport does not write rendered mail to application logs.', 'Production mail transport must not use log or array, including failover members.');
        $checks[] = $this->mailCredentialCheck($mailers);
        $checks[] = $this->check('mail_from_address', $this->productionAddress(config('mail.from.address')), 'Mail sender address is configured without an example/test domain.', 'MAIL_FROM_ADDRESS must be a production sender address.');

        $checks[] = $this->check('queue_connection', ! in_array(config('queue.default'), ['sync', 'null', 'deferred'], true), 'Queue uses an asynchronous backend.', 'Production queue must use an asynchronous backend.');
        $checks[] = $this->check('filesystem_default', config('filesystems.default') === 'private', 'Default filesystem is the private disk.', 'FILESYSTEM_DISK must be private.');
        $checks[] = $this->diskCheck('private');
        $checks[] = $this->diskCheck('quarantine');
        $checks[] = $this->check(
            'storage_separation',
            $this->normalizedPath(config('filesystems.disks.private.root')) !== $this->normalizedPath(config('filesystems.disks.quarantine.root')),
            'Private and quarantine storage roots are distinct.',
            'Private and quarantine storage roots must be distinct.',
        );

        $checks[] = $this->check('malware_driver', config('files.malware_scan_driver') === 'clamav', 'Production malware driver is ClamAV.', 'MALWARE_SCAN_DRIVER must be clamav.');
        $checks[] = $this->check('malware_signature_age', (int) config('files.clamav_signature_max_age_hours') > 0, 'Maximum signature age is configured.', 'CLAMAV_SIGNATURE_MAX_AGE_HOURS must be greater than zero.');
        $checks[] = $this->check('document_retention', is_numeric(config('files.retention_days')) && (int) config('files.retention_days') > 0, 'Document retention duration is configured.', 'DOCUMENT_RETENTION_DAYS must be an approved positive value.');

        $checks[] = $this->check('payment_driver', config('services.xendit.driver') === 'xendit', 'Production Xendit driver is selected.', 'XENDIT_DRIVER must be xendit.');
        $checks[] = $this->check('xendit_secret', $this->present(config('services.xendit.secret_key')), 'Xendit secret key is present.', 'XENDIT_SECRET_KEY is missing.');
        $checks[] = $this->check('xendit_callback_token', $this->present(config('services.xendit.callback_token')), 'Xendit callback token is present.', 'XENDIT_CALLBACK_TOKEN is missing.');

        $checks[] = $this->check('database_driver', config('database.default') === 'pgsql', 'PostgreSQL is the configured database driver.', 'DB_CONNECTION must be pgsql.');
        $logLevels = $this->selectedLogLevels((string) config('logging.default'));
        $checks[] = $this->check('log_level', $logLevels !== [] && ! in_array('debug', $logLevels, true), 'Active log channels do not use the debug level.', 'Production log channels must not use the debug level.');

        $checks[] = [
            'name' => 'tls_terminator',
            'status' => 'NOT_VERIFIABLE',
            'detail' => 'TLS termination, forwarded-proto trust, and HTTP-to-HTTPS redirect require deployment evidence.',
        ];
        $checks[] = [
            'name' => 'hsts',
            'status' => 'NOT_VERIFIABLE',
            'detail' => 'HSTS is assigned to the production TLS layer and requires response-header evidence there.',
        ];

        return $checks;
    }

    /** @return array{name:string,status:string,detail:string} */
    private function check(string $name, bool $passes, string $pass, string $fail): array
    {
        return [
            'name' => $name,
            'status' => $passes ? 'PASS' : 'FAIL',
            'detail' => $passes ? $pass : $fail,
        ];
    }

    /** @param list<string> $transports
     * @return array{name:string,status:string,detail:string}
     */
    private function mailCredentialCheck(array $transports): array
    {
        if (! in_array('smtp', $transports, true)) {
            return [
                'name' => 'mail_credentials',
                'status' => 'NOT_VERIFIABLE',
                'detail' => 'The selected non-SMTP provider credentials require provider-specific deployment verification.',
            ];
        }

        $configured = $this->present(config('mail.mailers.smtp.host'))
            && $this->present(config('mail.mailers.smtp.port'))
            && $this->present(config('mail.mailers.smtp.username'))
            && $this->present(config('mail.mailers.smtp.password'));

        return $this->check('mail_credentials', $configured, 'Authenticated SMTP settings are present.', 'Authenticated SMTP host, port, username, and password must be present.');
    }

    /** @return list<string> */
    private function selectedMailerTransports(string $mailer, array $seen = []): array
    {
        if ($mailer === '' || in_array($mailer, $seen, true)) {
            return [];
        }

        $configuration = config('mail.mailers.'.$mailer);
        if (! is_array($configuration)) {
            return [];
        }

        $transport = (string) ($configuration['transport'] ?? $mailer);
        if (! in_array($transport, ['failover', 'roundrobin'], true)) {
            return [$transport];
        }

        $transports = [];
        foreach ($configuration['mailers'] ?? [] as $nestedMailer) {
            $transports = [
                ...$transports,
                ...$this->selectedMailerTransports((string) $nestedMailer, [...$seen, $mailer]),
            ];
        }

        return array_values(array_unique($transports));
    }

    /** @return list<string> */
    private function selectedLogLevels(string $channel, array $seen = []): array
    {
        if ($channel === '' || in_array($channel, $seen, true)) {
            return [];
        }

        $configuration = config('logging.channels.'.$channel);
        if (! is_array($configuration)) {
            return [];
        }

        if (($configuration['driver'] ?? null) !== 'stack') {
            return [strtolower((string) ($configuration['level'] ?? 'debug'))];
        }

        $levels = [];
        foreach ($configuration['channels'] ?? [] as $nestedChannel) {
            $levels = [
                ...$levels,
                ...$this->selectedLogLevels((string) $nestedChannel, [...$seen, $channel]),
            ];
        }

        return array_values(array_unique($levels));
    }

    /** @return array{name:string,status:string,detail:string} */
    private function diskCheck(string $disk): array
    {
        $root = $this->normalizedPath(config('filesystems.disks.'.$disk.'.root'));
        $public = $this->normalizedPath(public_path());
        $safe = config('filesystems.disks.'.$disk.'.driver') === 'local'
            && config('filesystems.disks.'.$disk.'.serve') === false
            && $root !== ''
            && ! str_starts_with($root.'/', $public.'/');

        return $this->check(
            $disk.'_storage',
            $safe,
            ucfirst($disk).' storage is local, non-serving, and outside the web root.',
            ucfirst($disk).' storage must be local, non-serving, and outside the web root.',
        );
    }

    private function present(mixed $value): bool
    {
        return is_int($value) || (is_string($value) && trim($value) !== '');
    }

    private function productionAddress(mixed $address): bool
    {
        if (! is_string($address) || filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $host = strtolower((string) substr(strrchr($address, '@') ?: '', 1));

        return $host !== ''
            && ! str_ends_with($host, '.test')
            && ! in_array($host, ['example.com', 'example.test', 'localhost'], true);
    }

    private function normalizedPath(mixed $path): string
    {
        return strtolower(rtrim(str_replace('\\', '/', (string) $path), '/'));
    }
}
