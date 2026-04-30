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

    public function index($filters)
    {
        $query = $this->model->query();
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['group_id'])) {
            $query->where('group_id', $filters['group_id']);
        }
        return $query->with(['plaintiff', 'defendant', 'group' , 'plaintiffLawyer','defendantLawyer'])->latest()->paginate(10);
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

    public function getCasesStatus()
    {
      return $this->model
        ->selectRaw("
            COUNT(CASE WHEN status = 'new' THEN 1 END) as new_cases,
            COUNT(CASE WHEN status = 'on_going' THEN 1 END) as on_going_cases,
            COUNT(CASE WHEN status = 'appeal' THEN 1 END) as appeal_cases,
            COUNT(CASE WHEN status = 'execution' THEN 1 END) as execution_cases,
            COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_cases
        ")
        ->first();

    }
  
}
