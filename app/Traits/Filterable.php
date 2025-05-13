<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Filterable
{
    /**
     * Apply dynamic filters to the query
     *
     * @param Builder $query
     * @param Request $request
     * @return Builder
     */
    public function scopeFilter(Builder $query, $request): Builder
    {
        // Support both direct Request object and already parsed array of filters
        $filters = $request instanceof Request
            ? $request->input('filters', [])
            : (is_array($request) ? $request : []);

        // Apply filters dynamically
        $this->applyFilters($query, $filters);

        // Apply search if exists
        if (isset($filters['search']) && !empty($filters['search'])) {
            $this->applySearch($query, $filters['search']);
        }

        // Apply trashed filter if it exists
        if (isset($filters['trashed'])) {
            $this->applyTrashedFilter($query, $filters['trashed']);
        }

        return $query;
    }

    /**
     * Apply sort to the query
     *
     * @param Builder $query
     * @param Request|array $request
     * @param string $defaultSort
     * @param string $defaultDirection
     * @return Builder
     */
    public function scopeSort(Builder $query, $request, string $defaultSort = 'created_at', string $defaultDirection = 'desc'): Builder
    {
        // Handle both Request object and already parsed array
        if ($request instanceof Request) {
            $sort = $request->input('sort', ['field' => $defaultSort, 'direction' => $defaultDirection]);
        } else {
            $sort = is_array($request) ? $request : ['field' => $defaultSort, 'direction' => $defaultDirection];
        }

        $sortField = $sort['field'] ?? $defaultSort;
        $direction = $sort['direction'] ?? $defaultDirection;

        // Sanitize direction
        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = $defaultDirection;
        }

        return $query->orderBy($sortField, $direction);
    }

    /**
     * Apply filters to the query
     *
     * @param Builder $query
     * @param array $filters
     * @return void
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        $allowedFields = $this->filterableFields ?? [];

        foreach ($filters as $field => $value) {
            // Skip special filters that are handled separately
            if ($field === 'search' || $field === 'trashed' || !in_array($field, $allowedFields)) {
                continue;
            }

            if (is_array($value)) {
                // Check if this is a range filter with min/max or from/to
                if (isset($value['min']) || isset($value['max']) || isset($value['from']) || isset($value['to'])) {
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
                    // Handle multi-value filtering (WHERE IN)
                    $query->whereIn($field, $value);
                }
            } else {
                $query->where($field, $value);
            }
        }
    }

    /**
     * Apply search to the query
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
     * Apply trashed filter to the query
     *
     * @param Builder $query
     * @param string|bool|null $trashed
     * @return void
     */
    protected function applyTrashedFilter(Builder $query, $trashed): void
    {
        // Make sure model uses soft deletes
        if (!method_exists($this, 'getDeletedAtColumn')) {
            return;
        }

        // Filter based on trashed value
        if ($trashed === 'only') {
            $query->onlyTrashed();
        } elseif ($trashed === 'with') {
            $query->withTrashed();
        } elseif ($trashed === true || $trashed === 'true' || $trashed === '1') {
            $query->onlyTrashed();
        }
        // Otherwise, default Laravel behavior will filter out trashed records
    }
}
