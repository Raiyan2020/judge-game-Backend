<?php

namespace App\Services;

 use App\Repositories\LegalCaseRepository;


class LegalCaseOpinionServices
{
    public function __construct(protected LegalCaseRepository $repo) {}


    public function createOpinion($request)
    {
        $legalCase = $this->repo->find($request['legal_case_id']);
        if (!$legalCase) {
            throw new \Exception(__('Legal case not found'));
        }
        $opinionData = [
            'user_id' => auth()->id(),
            'opinion' => $request['opinion'],
            'final_requests' => $request['final_requests'] ?? null,
            'role'=> $request['role'] ?? null,
        ];
         $legalCaseOpinion = $legalCase->opinions()->create($opinionData);
         $attachments = $this->collectAttachments($request);
        $this->uploadAttachments($legalCaseOpinion, $attachments);
        return $legalCase;
    }

       private function collectAttachments($request)
    {
        return [
            'images' => $request['images'] ?? [],
            'videos' => $request['videos'] ?? [],
            'audios' => $request['audios'] ?? [],
        ];
    }

    private function uploadAttachments($model, $attachments)
    {
        foreach ($attachments as $collection => $files) {
            foreach ($files as $file) {
                $model
                    ->addMedia($file)
                    ->toMediaCollection($collection);
            }
        }
    }

}
