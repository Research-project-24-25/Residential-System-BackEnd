<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Filterable
{
    protected function applySearch(Builder $query, Request $request, array $searchableFields): Builder
    {
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function (Builder $q) use ($searchTerm, $searchableFields) {
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'LIKE', "%{$searchTerm}%");
                }
            });
        }

        return $query;
    }

    protected function applySorting(
        Builder $query,
        Request $request,
        string $defaultSort = 'created_at',
        string $defaultDirection = 'desc'
    ): Builder {
        $sort = $request->get('sort', $defaultSort);
        $direction = $request->get('direction', $defaultDirection);

        // Sanitize direction
        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = $defaultDirection;
        }

        return $query->orderBy($sort, $direction);
    }

    protected function applyPagination(Builder $query, Request $request, int $defaultPerPage = 10)
    {
        $perPage = (int)$request->get('per_page', $defaultPerPage);

        // Cap per_page to a reasonable limit
        if ($perPage < 1 || $perPage > 100) {
            $perPage = $defaultPerPage;
        }

        return $query->paginate($perPage);
    }
}
