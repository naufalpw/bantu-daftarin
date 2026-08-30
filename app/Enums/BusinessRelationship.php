<?php

namespace App\Enums;

enum BusinessRelationship: string
{
    case OWNER = 'OWNER';
    case DIRECTOR = 'DIRECTOR';
    case MANAGEMENT = 'MANAGEMENT';
    case EMPLOYEE = 'EMPLOYEE';
    case AUTHORIZED_REPRESENTATIVE = 'AUTHORIZED_REPRESENTATIVE';
    case OTHER = 'OTHER';
}
