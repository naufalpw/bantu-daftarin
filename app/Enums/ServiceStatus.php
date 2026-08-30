<?php

namespace App\Enums;

enum ServiceStatus: string
{
    case DRAFT = 'DRAFT';
    case COMING_SOON = 'COMING_SOON';
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
}
