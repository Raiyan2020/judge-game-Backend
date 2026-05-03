<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

#[Fillable(['type', 'content', 'legal_case_id', 'actor_id', 'subject_id', 'group_id'])]

class LegalCaseNews extends Model
{
    use HasTranslations;
    public $translatable = ['content'];
    protected $table = 'legal_case_news';


    #relationships
    public function legalCase()
    {
        return $this->belongsTo(LegalCase::class, 'legal_case_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject()
    {
        return $this->belongsTo(LegalCase::class, 'subject_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }


    public function generateContent()
    {
        switch ($this->type) {
            case 'case_created':
                return $this->content . " " . __('against') . " " . $this->actor?->name;
            case 'opinion_added':
                return "تم إضافة رأي جديد";
            case 'case_first_judgment':
                return $this->content . " " . __('by') . " " . $this->actor?->name;    

            default:
                return $this->content;
        }
    }
}
