<?php

namespace App\Enums;

enum ChatPollType: string
{
    case ADS = 'ads';
    case CREATE_LAW = 'create_law';
    case UPDATE_LAW = 'update_law'; 
    case DELETE_LAW = 'delete_law';

}
