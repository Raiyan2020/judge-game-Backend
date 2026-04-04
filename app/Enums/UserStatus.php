<?php

namespace App\Enums;

enum UserStatus: string
{
    case ONLINE = 'online';
    case OFFLINE = 'offline';
    case OUTSIDE = 'outside';
}
