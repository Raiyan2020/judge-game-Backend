<?php

namespace App\Enums;

enum LegalCaseStatus: string
{
    case NEW = 'new';
    case ONGOING = 'ongoing';
    case APPEAL = 'appeal';
    case EXECUTION = 'execution';
    case CLOSED = 'closed';
}