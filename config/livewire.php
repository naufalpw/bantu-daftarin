<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Content Security Policy Compatibility
    |--------------------------------------------------------------------------
    |
    | The application deliberately disallows unsafe-eval. Use Livewire's
    | CSP-safe client bundle so wire expressions remain interactive without
    | weakening the application's script policy.
    |
    */
    'csp_safe' => true,
];
