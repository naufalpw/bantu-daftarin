<?php

namespace App\Enums;

enum DocumentScanStatus: string
{
    case QUARANTINED = 'QUARANTINED';
    case PASSED = 'PASSED';
    case FAILED = 'FAILED';
    case UNAVAILABLE = 'UNAVAILABLE';
}
