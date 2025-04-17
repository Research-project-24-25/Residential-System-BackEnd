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
        $filters = $request->input('filters', []);

        // Price range filter
        if (isset($filters['price'])) {
            if (isset($filters['price']['min'])) {
                $query->where('price', '>=', $filters['price']['min']);
            }
            if (isset($filters['price']['max'])) {
                $query->where('price', '<=', $filters['price']['max']);
            }
        }

        // Bedrooms filter
        if (isset($filters['bedrooms'])) {
            if (isset($filters['bedrooms']['min'])) {
                $query->where('bedrooms', '>=', $filters['bedrooms']['min']);
            }
            if (isset($filters['bedrooms']['max'])) {
                $query->where('bedrooms', '<=', $filters['bedrooms']['max']);
            }
        }

        // Bathrooms filter
        if (isset($filters['bathrooms'])) {
            if (isset($filters['bathrooms']['min'])) {
                $query->where('bathrooms', '>=', $filters['bathrooms']['min']);
            }
            if (isset($filters['bathrooms']['max'])) {
                $query->where('bathrooms', '<=', $filters['bathrooms']['max']);
            }
        }

        // Area filter
        if (isset($filters['area'])) {
            if (isset($filters['area']['min'])) {
                $query->where('area', '>=', $filters['area']['min']);
            }
            if (isset($filters['area']['max'])) {
                $query->where('area', '<=', $filters['area']['max']);
            }
        }

        // Status filter
        if (isset($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        // Price type filter
        if (isset($filters['price_type'])) {
            if (is_array($filters['price_type'])) {
                $query->whereIn('price_type', $filters['price_type']);
            } else {
                $query->where('price_type', $filters['price_type']);
            }
        }

        // Currency filter
        if (isset($filters['currency'])) {
            if (is_array($filters['currency'])) {
                $query->whereIn('currency', $filters['currency']);
            } else {
                $query->where('currency', $filters['currency']);
            }
        }

        // Features filter (JSON contains)
        if (isset($filters['features'])) {
            $features = $filters['features'];
            if (is_array($features)) {
                foreach ($features as $feature) {
                    $query->whereJsonContains('features', $feature);
                }
            } else {
                $query->whereJsonContains('features', $features);
            }
        }
    }

    /**
     * Apply apartment-specific filters
     */
    protected function applyApartmentFilters(Builder $query, Request $request): void
    {
        $filters = $request->input('filters', []);

        // Building filter
        if (isset($filters['building_id'])) {
            $query->whereHas('floor', function ($q) use ($filters) {
                $q->where('building_id', $filters['building_id']);
            });
        }

        // Floor filter
        if (isset($filters['floor_id'])) {
            $query->where('floor_id', $filters['floor_id']);
        }

        // Floor number filter
        if (isset($filters['floor_number'])) {
            $query->whereHas('floor', function ($q) use ($filters) {
                $q->where('number', $filters['floor_number']);
            });
        }
    }

    /**
     * Apply house-specific filters
     */
    protected function applyHouseFilters(Builder $query, Request $request): void
    {
        $filters = $request->input('filters', []);

        // Lot size filter
        if (isset($filters['lot_size'])) {
            if (isset($filters['lot_size']['min'])) {
                $query->where('lot_size', '>=', $filters['lot_size']['min']);
            }
            if (isset($filters['lot_size']['max'])) {
                $query->where('lot_size', '<=', $filters['lot_size']['max']);
            }
        }

        // Property style filter
        if (isset($filters['property_style'])) {
            if (is_array($filters['property_style'])) {
                $query->whereIn('property_style', $filters['property_style']);
            } else {
                $query->where('property_style', $filters['property_style']);
            }
        }
    }

    /**
     * Apply search to apartment query
     */
    protected function applyApartmentSearch(Builder $query, Request $request): void
    {
        $filters = $request->input('filters', []);

        if (isset($filters['search'])) {
            $search = $filters['search'];
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
        $filters = $request->input('filters', []);

        if (isset($filters['search'])) {
            $search = $filters['search'];
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
        $filters = $request->input('filters', []);

        // Created date range filter
        if (isset($filters['created_at'])) {
            if (isset($filters['created_at']['from'])) {
                $query->whereDate('created_at', '>=', $filters['created_at']['from']);
            }
            if (isset($filters['created_at']['to'])) {
                $query->whereDate('created_at', '<=', $filters['created_at']['to']);
            }
        }

        // Updated date range filter
        if (isset($filters['updated_at'])) {
            if (isset($filters['updated_at']['from'])) {
                $query->whereDate('updated_at', '>=', $filters['updated_at']['from']);
            }
            if (isset($filters['updated_at']['to'])) {
                $query->whereDate('updated_at', '<=', $filters['updated_at']['to']);
            }
        }
    }

    /**
     * Combine and sort properties from different models
     */
    public function combineAndSort(Collection $apartments, Collection $houses, Request $request): Collection
    {
        $allProperties = $apartments->concat($houses);

        // Apply manual sorting based on sort parameter
        $sort = $request->input('sort', ['field' => 'created_at', 'direction' => 'desc']);
        $sortField = $sort['field'] ?? 'created_at';
        $direction = $sort['direction'] ?? 'desc';

        // Sanitize direction
        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = 'desc';
        }

        // Sort the combined collection
        return $allProperties->sortBy([$sortField, $direction]);
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
