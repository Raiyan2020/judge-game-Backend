<?php

namespace App\Repositories;

use App\Models\LegalCase;
use App\Models\LegalCaseNews;

class LegalCaseNewsRepository extends BaseRepository
{
    /**
     * LegalCaseNewsRepository constructor.
     * @param LegalCaseNews $model
     */
    public function __construct(LegalCaseNews $model)
    {
        parent::__construct($model);
    }

    public function index($filters)
    {
        $query = $this->model->query();
        if (!empty($filters['date'])) {
            $query = $this->applyDateFilter($query, $filters['date']);
        }

        // Scope to the caller's own groups. News now includes GROUP-level events
        // (role/permission/law changes) — leaking a group's internal events to
        // non-members would expose group names, member names and case details.
        // (Case news is scoped the same way; a user has no business seeing cases
        // from groups they aren't in.)
        $userId = auth('sanctum')->id();
        if ($userId) {
            $groupIds = \App\Models\Group::query()
                ->where('user_id', $userId)
                ->orWhereHas('users', function ($q) use ($userId) {
                    $q->where('users.id', $userId)
                        ->where('group_user.status', 'accepted');
                })
                ->pluck('id');
            $query->whereIn('group_id', $groupIds);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query->with(['actor', 'subject', 'group'])->latest()->paginate(10);
    }

    private function applyDateFilter($query, $date)
    {
        return match ($date) {
            'today' => $query->whereDate('created_at', now()),
            'yesterday' => $query->whereDate('created_at', now()->subDay()),

            'this_week' => $query->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ]),

            'last_week' => $query->whereBetween('created_at', [
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek(),
            ]),

            'this_month' => $query->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year),

            'last_month' => $query->whereMonth('created_at', now()->subMonth()->month)
                ->whereYear('created_at', now()->subMonth()->year),

            default => $query,
        };
    }
}
