<?php

namespace App\Enums;

enum AuthChallengeType: string
{
    case CLIENT_LOGIN = 'CLIENT_LOGIN';
    case ADMIN_LOGIN = 'ADMIN_LOGIN';
}
