<?php

namespace App\Enums;

enum LegalCaseJudgmentStage: string
{
    case FIRST_INSTANCE = 'first_instance';
    case APPEAL = 'appeal';
}
