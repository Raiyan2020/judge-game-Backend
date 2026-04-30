<?php

namespace App\Enums;

enum LegalCaseStatus: string
{
    case NEW = 'new';
    case IN_PROGRESS = 'in_progress';
    case APPEAL = 'appeal';
    case CLOSED = 'closed';
}