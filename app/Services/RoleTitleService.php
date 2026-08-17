<?php

namespace App\Services;

use App\Enums\GroupRole;
use App\Repositories\RoleTitleRepository;

class RoleTitleService
{


    public function __construct(protected RoleTitleRepository $repo) {}

    public function getRoles(): array
    {
        return collect(GroupRole::cases())
            ->mapWithKeys(fn($role) => [
                $role->value => __($role->value)
            ])
            ->toArray();
    }
    public function create($request)
    {
        $title = $this->repo->create($this->normalizePayload($request));
        $title->requirements()->createMany($this->normalizeActions($request['actions']));

        return $title;
    }

    public function update($model, $request)
    {
        $title = $this->repo->update($model, $this->normalizePayload($request));
        $model->requirements()->delete();
        $model->requirements()->createMany($this->normalizeActions($request['actions']));

        return $model;
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    private function normalizePayload(array $request): array
    {
        return collect($request)->except('actions')->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $actions
     * @return array<int, array<string, mixed>>
     */
    private function normalizeActions(array $actions): array
    {
        return collect($actions)->map(function ($action) {
            $action['required_count'] = max(
                0,
                min((int) ($action['required_count'] ?? 0), \App\Models\RoleAction::MAX_POINTS)
            );

            return $action;
        })->all();
    }

    public function delete($model)
    {
        return $this->repo->delete($model);
    }
}
