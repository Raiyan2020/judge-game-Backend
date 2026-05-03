<?php

namespace App\Enums;

enum LegalCaseJudgmentType: string
{
    case CONVICTION = 'conviction';
    case ACQUITTAL = 'acquittal';
    case DISMISSED = 'dismissed';
}
