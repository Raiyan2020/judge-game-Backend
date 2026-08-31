<?php

namespace App\Enums;

enum LegalCaseStatus: string
{
    // Full lifecycle order:
    // pending_lawyer → new → in_progress → ongoing → appeal → execution → closed
    //
    // `pending_lawyer` — filed, held with the plaintiff lawyer, NOT yet
    // officially filed (the case only becomes `new` once the plaintiff lawyer
    // writes their opinion). `in_progress` — being heard, pre-ruling (the judge
    // has scheduled a hearing or requested an opinion). `ongoing` keeps its
    // meaning: a first-instance judgment has been issued.
    case PENDING_LAWYER = 'pending_lawyer';
    case NEW = 'new';
    case IN_PROGRESS = 'in_progress';
    case ONGOING = 'ongoing';
    case APPEAL = 'appeal';
    case EXECUTION = 'execution';
    case CLOSED = 'closed';
}