<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use Filterable, HasFactory, SoftDeletes;

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
        'acquisition_cost',
        'acquisition_date',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'area' => 'integer',
        'occupancy_limit' => 'integer',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'images' => 'array',
        'features' => 'array',
        'acquisition_cost' => 'decimal:2',
        'acquisition_date' => 'date',
    ];

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

    protected array $searchableFields = [
        'label',
        'description',
    ];

    public function getImagesAttribute($images)
    {
        if (empty($images)) {
            return [];
        }

        $imagesArray = is_string($images) ? json_decode($images, true) : $images;

        if (!is_array($imagesArray)) {
            return [];
        }

        return array_map(fn($image) => url($image), $imagesArray);
    }

    public function setImagesAttribute($images)
    {
        if (is_null($images)) {
            $this->attributes['images'] = json_encode([]);
            return;
        }

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

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }
    /**
     * The services associated with the property.
     */
    public function services()
    {
        return $this->belongsToMany(Service::class, 'property_service')
            ->withPivot([
                'billing_type',
                'price',
                'status',
                'details',
                'activated_at',
                'expires_at',
                'last_billed_at',
            ])
            ->withTimestamps();
    }

    public function recurringBills()
    {
        return $this->bills()->whereNotNull('recurrence')->whereNotNull('next_billing_date');
    }

    public function buyers()
    {
        return $this->residents()->wherePivotIn('relationship_type', ['buyer', 'co_buyer']);
    }

    public function renters()
    {
        return $this->residents()->where('relationship_type', 'renter');
    }

    public function isApartment(): bool
    {
        return $this->type === 'apartment';
    }

    public function isHouse(): bool
    {
        return $this->type === 'house';
    }

    public function getPropertyTypeAttribute(): string
    {
        return ucfirst($this->type);
    }

    public function getPropertyNumberAttribute(): string
    {
        return $this->label;
    }

    public function getOverdueBillsAttribute()
    {
        return $this->bills()->overdue()->get();
    }

    public function getUnpaidBillsTotalAttribute(): float
    {
        return $this->bills()->unpaid()->sum('amount');
    }
}
