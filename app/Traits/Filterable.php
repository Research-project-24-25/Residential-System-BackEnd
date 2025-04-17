<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Filterable
{
    /**
     * Apply all filters to the query builder.
     *
     * @param Builder $query
     * @param Request $request
     * @return Builder
     */
    public function scopeFilter(Builder $query, Request $request): Builder
    {
        $filters = $request->input('filters', []);

        // Apply filters dynamically
        $this->applyFilters($query, $filters);

        // Apply search if exists
        if (isset($filters['search']) && !empty($filters['search'])) {
            $this->applySearch($query, $filters['search']);
        }

        return $query;
    }

    /**
     * Dynamically apply filters to the query.
     *
     * @param Builder $query
     * @param array $filters
     * @return void
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        $allowedFields = $this->filterableFields ?? [];

        foreach ($filters as $field => $value) {
            if (!in_array($field, $allowedFields)) {
                continue;
            }

            if (is_array($value)) {
                if (isset($value['min'])) {
                    $query->where($field, '>=', $value['min']);
                }
                if (isset($value['max'])) {
                    $query->where($field, '<=', $value['max']);
                }
                if (isset($value['from'])) {
                    $query->whereDate($field, '>=', $value['from']);
                }
                if (isset($value['to'])) {
                    $query->whereDate($field, '<=', $value['to']);
                }
            } else {
                $query->where($field, $value);
            }
        }
    }

    /**
     * Apply search to the query based on searchable fields.
     *
     * @param Builder $query
     * @param string $searchTerm
     * @return Builder
     */
    protected function applySearch(Builder $query, string $searchTerm): Builder
    {
        $searchableFields = $this->searchableFields ?? [];

        if (!empty($searchableFields) && !empty($searchTerm)) {
            $query->where(function (Builder $q) use ($searchTerm, $searchableFields) {
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'LIKE', "%{$searchTerm}%");
                }
            });
        }

        return $query;
    }

    /**
     * Apply sorting to the query.
     *
     * @param Builder $query
     * @param Request $request
     * @param string $defaultSort
     * @param string $defaultDirection
     * @return Builder
     */
    public function scopeSort(Builder $query, Request $request, string $defaultSort = 'created_at', string $defaultDirection = 'desc'): Builder
    {
        $sort = $request->input('sort', ['field' => $defaultSort, 'direction' => $defaultDirection]);

        $sortField = $sort['field'] ?? $defaultSort;
        $direction = $sort['direction'] ?? $defaultDirection;

        // Sanitize direction
        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = $defaultDirection;
        }

        return $query->orderBy($sortField, $direction);
    }
}
