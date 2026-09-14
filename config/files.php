<?php

return [
    'malware_scan_driver' => env('MALWARE_SCAN_DRIVER', 'clamav'),
    'clamav_binary' => env('CLAMAV_BINARY', 'clamdscan'),
    'clamav_signature_max_age_hours' => (int) env('CLAMAV_SIGNATURE_MAX_AGE_HOURS', 48),
    'retention_days' => env('DOCUMENT_RETENTION_DAYS'),
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf'],
    'allowed_mimes' => ['image/jpeg', 'image/png', 'application/pdf'],
];
