<?php

namespace App\Enums;

enum CaseRole: string
{
    case PLAINTIFF = 'plaintiff';
    case DEFENDANT = 'defendant';
    case WITNESS = 'witness';
    case PLAINTIFF_LAWYER = 'plaintiff_lawyer';
    case DEFENDANT_LAWYER = 'defendant_lawyer';
    case JUDGE = 'judge'; 
    case CONSULTANT = 'consultant'; 
}