<?php

namespace App\Enums;

enum DocumentReviewAction: string
{
    case ACCEPT = 'ACCEPT';
    case REJECT = 'REJECT';
    case REQUEST_REVISION = 'REQUEST_REVISION';
}
