<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalCaseJudgment extends Model
{
    protected $fillable = [
        'legal_case_id',
        'judgment_type',
        'stage',
        'judgment_text',
        'judged_by',
        'is_final'
    ];

    public function legalCase()
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function judge()
    {
        return $this->belongsTo(User::class, 'judged_by');
    }
}