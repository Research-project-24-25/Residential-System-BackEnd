<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Apartment extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'floor_id',
        'number',
        'price',
        'currency',
        'price_type',
        'status',
        'description',
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
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'area' => 'integer',
        'images' => 'array', // Cast JSON column to array
        'features' => 'array', // Cast JSON column to array
    ];

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * Get the residents living in this apartment
     */
    public function residents(): HasMany
    {
        return $this->hasMany(Resident::class);
    }

    /**
     * Get the full compound address for the apartment.
     * Example: "Tower A, Floor 5, Apt 502"
     *
     * @return string
     */
    public function getCompoundAddressAttribute(): string
    {
        // Eager load relationships if not already loaded to avoid N+1 issues
        $this->loadMissing('floor.building');

        $floor = $this->floor;
        $building = $floor?->building;

        $parts = [];
        if ($building?->identifier) {
            $parts[] = $building->identifier;
        }
        if ($floor?->number) {
            $parts[] = "Floor " . $floor->number;
        }
        if ($this->number) {
            $parts[] = "Apt " . $this->number;
        }

        return implode(', ', $parts);
    }
}
