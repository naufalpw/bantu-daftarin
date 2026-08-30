<?php

namespace App\Enums;

enum ResultDocumentType: string
{
    case PRIMARY_RESULT = 'PRIMARY_RESULT';
    case SUPPORTING_DOCUMENT = 'SUPPORTING_DOCUMENT';
    case RECEIPT = 'RECEIPT';
    case REPORT = 'REPORT';
    case OTHER = 'OTHER';
}
