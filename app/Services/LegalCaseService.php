<?php

namespace App\Services;

use App\Repositories\LegalCaseRepository;
use Illuminate\Support\Facades\DB;

class LegalCaseService
{


    public function __construct(protected LegalCaseRepository $repo) {}

    public function create($request)
    {
        try {
            DB::beginTransaction();
            $participants = $request['participants'];
            $request['user_id'] = auth()->id();
            $legalCase = $this->repo->create($request);
            $attachments = $this->collectAttachments($request);
            $this->uploadAttachments($legalCase, $attachments);
            $legalCase->participants()->createMany($participants);
            DB::commit();

            return $legalCase;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(__('Failed to create legal case. Please try again later.'));
        }
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

    public function update($model, $request)
    {
        return $this->repo->update($model, $request);
    }

    public function delete($model)
    {
        return $this->repo->delete($model);
    }

    public function activation($model)
    {
        return $this->repo->activation($model);
    }
}
