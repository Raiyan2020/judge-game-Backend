<?php

namespace App\Repositories;

use App\Models\LegalCase;

class LegalCaseRepository extends BaseRepository
{
    /**
     * LegalCaseRepository constructor.
     * @param LegalCase $model
     */
    public function __construct(LegalCase $model)
    {
        parent::__construct($model);
    }

    public function createCaseNews($legalCase, $type, $content, $actorId , $subjectId )
    {
         $news = $legalCase->news()->create([
            'type' => $type,
            'content' => $content,
            'actor_id' => $actorId,
            'group_id' => $legalCase->group_id,
            'subject_id' => $subjectId,
        ]);
        return $news;
    }
  
}
