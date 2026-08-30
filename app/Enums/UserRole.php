<?php

namespace App\Enums;

enum UserRole: string
{
    case CLIENT = 'CLIENT';
    case SUPER_ADMIN = 'SUPER_ADMIN';
}
