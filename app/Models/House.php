<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class House extends Model
{
    use HasFactory;

    protected $fillable = [
        'identifier',
        'price',
        'currency',
        'price_type',
        'status',
        'description',
        'bedrooms',
        'bathrooms',
        'area',
        'lot_size',
        'property_style',
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
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'area' => 'integer',
        'lot_size' => 'integer',
        'images' => 'array', // Cast JSON column to array
        'features' => 'array', // Cast JSON column to array
    ];

    /**
     * Get the residents living in this house
     */
    public function residents(): HasMany
    {
        return $this->hasMany(Resident::class);
    }

    /**
     * Get the full compound address for the house.
     * Example: "Villa 27"
     *
     * @return string
     */
    public function getCompoundAddressAttribute(): string
    {
        return $this->identifier ?? '';
    }
}
