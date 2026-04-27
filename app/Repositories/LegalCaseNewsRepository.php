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
