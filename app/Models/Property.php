<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use Filterable, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'label',
        'type',
        'price',
        'currency',
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
        'label',
        'description',
    ];

    /**
     * Get full image URLs
     *
     * @param mixed $images
     * @return array
     */
    public function getImagesAttribute($images)
    {
        if (empty($images)) {
            return [];
        }

        $imagesArray = is_string($images) ? json_decode($images, true) : $images;

        if (empty($imagesArray)) {
            return [];
        }

        return array_map(function ($image) {
            return url($image);
        }, $imagesArray);
    }

    /**
     * Set images as JSON
     *
     * @param array $images
     * @return void
     */
    public function setImagesAttribute($images)
    {
        $this->attributes['images'] = is_array($images) ? json_encode($images) : $images;
    }

    // Relationships
    public function residents()
    {
        return $this->belongsToMany(Resident::class)
            ->using(PropertyResident::class)
            ->withPivot([
                'relationship_type',
                'sale_price',
                'ownership_share',
                'monthly_rent',
                'start_date',
                'end_date',
            ])
            ->withTimestamps();
    }

    /**
     * Get all bills for this property.
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * Get all recurring bills for this property.
     */
    public function recurringBills()
    {
        return $this->bills()->whereNotNull('recurrence')->whereNotNull('next_billing_date');
    }

    /**
     * Get buyers of this property.
     */
    public function buyers()
    {
        return $this->residents()->wherePivotIn('relationship_type', ['buyer', 'co_buyer']);
    }

    /**
     * Get renters of this property.
     */
    public function renters()
    {
        return $this->residents()->where('relationship_type', 'renter');
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
        return $this->label;
    }

    /**
     * Get overdue bills for this property.
     */
    public function getOverdueBillsAttribute()
    {
        return $this->bills()->overdue()->get();
    }

    /**
     * Get total unpaid bills amount for this property.
     */
    public function getUnpaidBillsTotalAttribute(): float
    {
        return $this->bills()->unpaid()->sum('amount');
    }
}
