<?php

namespace App\Filters;

use App\Models\Apartment;
use App\Models\House;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PropertyFilter
{
    /**
     * Available filter fields for properties
     */
    protected array $filterableFields = [
        'common' => [
            'price',
            'bedrooms',
            'bathrooms',
            'area',
            'status',
            'price_type',
            'currency'
        ],
        'apartment' => [
            'building_id',
            'floor_id'
        ],
        'house' => [
            'lot_size',
            'property_style'
        ]
    ];

    /**
     * Handle filtering apartments based on request parameters
     */
    public function filterApartments(Request $request): Collection
    {
        $query = Apartment::query()->with(['floor.building', 'residents']);

        // Apply common filters
        $this->applyCommonFilters($query, $request);

        // Apply apartment-specific filters
        $this->applyApartmentFilters($query, $request);

        // Apply search
        $this->applyApartmentSearch($query, $request);

        // Apply date range filters
        $this->applyDateFilters($query, $request);

        // Apply sorting
        $this->applySorting($query, $request, 'created_at');

        return $query->get();
    }

    /**
     * Handle filtering houses based on request parameters
     */
    public function filterHouses(Request $request): Collection
    {
        $query = House::query()->with('residents');

        // Apply common filters
        $this->applyCommonFilters($query, $request);

        // Apply house-specific filters
        $this->applyHouseFilters($query, $request);

        // Apply search
        $this->applyHouseSearch($query, $request);

        // Apply date range filters
        $this->applyDateFilters($query, $request);

        // Apply sorting
        $this->applySorting($query, $request, 'created_at');

        return $query->get();
    }

    /**
     * Apply common filters to both property types
     */
    protected function applyCommonFilters(Builder $query, Request $request): void
    {
        // Price range filter
        $this->applyRangeFilter($query, $request, 'price', 'min_price', 'max_price');

        // Bedrooms filter
        $this->applyRangeFilter($query, $request, 'bedrooms', 'min_bedrooms', 'max_bedrooms');

        // Bathrooms filter
        $this->applyRangeFilter($query, $request, 'bathrooms', 'min_bathrooms', 'max_bathrooms');

        // Area filter
        $this->applyRangeFilter($query, $request, 'area', 'min_area', 'max_area');

        // Status filter
        $this->applyMultiValueFilter($query, $request, 'status');

        // Price type filter (sale, rent_monthly, etc.)
        $this->applyMultiValueFilter($query, $request, 'price_type');

        // Currency filter
        $this->applyMultiValueFilter($query, $request, 'currency', 'currency', false);

        // Features filter (JSON contains)
        $this->applyJsonContainsFilter($query, $request, 'features');
    }

    /**
     * Apply apartment-specific filters
     */
    protected function applyApartmentFilters(Builder $query, Request $request): void
    {
        // Building filter
        if ($request->filled('building_id')) {
            $this->applyRelationFilter($query, $request, 'floor', 'building_id');
        }

        // Floor filter
        if ($request->filled('floor_id')) {
            $query->where('floor_id', $request->input('floor_id'));
        }

        // Floor number filter
        if ($request->filled('floor_number')) {
            $this->applyRelationFilter($query, $request, 'floor', 'number', 'floor_number');
        }
    }

    /**
     * Apply house-specific filters
     */
    protected function applyHouseFilters(Builder $query, Request $request): void
    {
        // Lot size filter
        $this->applyRangeFilter($query, $request, 'lot_size', 'min_lot_size', 'max_lot_size');

        // Property style filter
        $this->applyMultiValueFilter($query, $request, 'property_style');
    }

    /**
     * Apply search to apartment query
     */
    protected function applyApartmentSearch(Builder $query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function (Builder $q) use ($search) {
                $q->where('number', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhereHas('floor.building', function ($buildingQuery) use ($search) {
                        $buildingQuery->where('identifier', 'LIKE', "%{$search}%");
                    });
            });
        }
    }

    /**
     * Apply search to house query
     */
    protected function applyHouseSearch(Builder $query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function (Builder $q) use ($search) {
                $q->where('identifier', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhere('property_style', 'LIKE', "%{$search}%");
            });
        }
    }

    /**
     * Apply date range filters to query
     */
    protected function applyDateFilters(Builder $query, Request $request): void
    {
        // Created date range filter
        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->input('created_from'));
        }

        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->input('created_to'));
        }

        // Updated date range filter
        if ($request->filled('updated_from')) {
            $query->whereDate('updated_at', '>=', $request->input('updated_from'));
        }

        if ($request->filled('updated_to')) {
            $query->whereDate('updated_at', '<=', $request->input('updated_to'));
        }
    }

    /**
     * Combine and sort properties from different models
     */
    public function combineAndSort(Collection $apartments, Collection $houses, Request $request): Collection
    {
        $allProperties = $apartments->concat($houses);

        // Apply manual sorting based on sort parameter
        $sortField = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');

        // Sanitize direction
        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = 'desc';
        }

        // Sort the combined collection
        return $allProperties->sortBy([$sortField, $direction]);
    }

    /**
     * Apply search to a query
     */
    protected function applySearch(
        Builder $query,
        Request $request,
        array $searchableFields,
        string $searchParam = 'search'
    ): Builder {
        if ($request->filled($searchParam)) {
            $searchTerm = $request->get($searchParam);
            $query->where(function (Builder $q) use ($searchTerm, $searchableFields) {
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'LIKE', "%{$searchTerm}%");
                }
            });
        }

        return $query;
    }

    /**
     * Apply sorting to a query
     */
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

    /**
     * Apply range filter to a query
     */
    protected function applyRangeFilter(
        Builder $query,
        Request $request,
        string $field,
        string $minParam = '',
        string $maxParam = ''
    ): Builder {
        // Apply minimum value filter
        if ($minParam && $request->filled($minParam)) {
            $query->where($field, '>=', $request->get($minParam));
        }

        // Apply maximum value filter
        if ($maxParam && $request->filled($maxParam)) {
            $query->where($field, '<=', $request->get($maxParam));
        }

        // Apply exact value filter (if min/max not used)
        if (empty($minParam) && empty($maxParam) && $request->filled($field)) {
            $query->where($field, $request->get($field));
        }

        return $query;
    }

    /**
     * Apply multi-value filter to a query
     */
    protected function applyMultiValueFilter(
        Builder $query,
        Request $request,
        string $field,
        string $param = '',
        bool $explodeComma = true
    ): Builder {
        $param = $param ?: $field;

        if ($request->filled($param)) {
            $values = $request->get($param);

            if ($explodeComma && is_string($values)) {
                $values = explode(',', $values);
            }

            if (is_array($values)) {
                $query->whereIn($field, $values);
            } else {
                $query->where($field, $values);
            }
        }

        return $query;
    }

    /**
     * Apply JSON contains filter for arrays
     */
    protected function applyJsonContainsFilter(
        Builder $query,
        Request $request,
        string $field,
        string $param = ''
    ): Builder {
        $param = $param ?: $field;

        if ($request->filled($param)) {
            $values = $request->get($param);

            if (is_string($values)) {
                $values = explode(',', $values);
            }

            foreach ((array)$values as $value) {
                $query->whereJsonContains($field, $value);
            }
        }

        return $query;
    }

    /**
     * Apply relationship filter
     */
    protected function applyRelationFilter(
        Builder $query,
        Request $request,
        string $relation,
        string $field,
        string $param = ''
    ): Builder {
        $param = $param ?: "{$relation}_{$field}";

        if ($request->filled($param)) {
            $value = $request->get($param);

            $query->whereHas($relation, function ($q) use ($field, $value) {
                if (is_array($value)) {
                    $q->whereIn($field, $value);
                } else {
                    $q->where($field, $value);
                }
            });
        }

        return $query;
    }
}
