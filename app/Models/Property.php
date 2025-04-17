<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use Filterable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'identifier',
        'type',
        'price',
        'currency',
        'price_type',
        'status',
        'description',
        'occupancy_limit',
        'bedrooms',
        'bathrooms',
        'area',
        'images',
        'features',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'area' => 'integer',
        'occupancy_limit' => 'integer',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'images' => 'array',
        'features' => 'array',
    ];

    /**
     * Define the filterable fields for this model.
     *
     * @var array<int, string>
     */
    protected array $filterableFields = [
        'type',
        'price',
        'currency',
        'price_type',
        'status',
        'occupancy_limit',
        'bedrooms',
        'bathrooms',
        'area',
        'created_at',
        'updated_at'
    ];

    /**
     * Define the searchable fields for this model.
     *
     * @var array<int, string>
     */
    protected array $searchableFields = [
        'identifier',
        'description',
    ];

    /**
     * Get the residents for the property.
     */
    public function residents(): HasMany
    {
        return $this->hasMany(Resident::class);
    }

    /**
     * Determine if the property is an apartment.
     *
     * @return bool
     */
    public function isApartment(): bool
    {
        return $this->type === 'apartment';
    }

    /**
     * Determine if the property is a house.
     *
     * @return bool
     */
    public function isHouse(): bool
    {
        return $this->type === 'house';
    }

    /**
     * Get formatted property type.
     *
     * @return string
     */
    public function getPropertyTypeAttribute(): string
    {
        return ucfirst($this->type);
    }

    /**
     * Get property number for display.
     *
     * @return string
     */
    public function getPropertyNumberAttribute(): string
    {
        return $this->identifier;
    }
}
