<?php

namespace App\Enum;

enum UserStatus: string
{
    case ONLINE = 'online';
    case OFFLINE = 'offline';
    case OUTSIDE = 'outside';
}
