<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\ResourceCollection;

class BaseCollection extends ResourceCollection
{
    public function __construct($resource, string $collects, $extra = null)
    {
        $this->collects = $collects;
        $this->extra = $extra;
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        return [
            'items' => $this->collection,
            'paginate' => [
                'total' => $this->total(),
                'count' => $this->count(),
                'per_page' => $this->perPage(),
                'next_page_url' => $this->nextPageUrl() ?? '',
                'prev_page_url' => $this->previousPageUrl() ?? '',
                'current_page' => $this->currentPage(),
                'total_pages' => $this->lastPage(),
            ],
            'extra' => $this->extra,
        ];
    }
}
