<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory, Filterable;

    protected $fillable = [
        'name',
        'description',
        'type',
        'base_price',
        'currency',
        'unit_of_measure',
        'is_recurring',
        'recurrence',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected array $filterableFields = [
        'type',
        'is_recurring',
        'is_active',
        'base_price',
        'created_at',
        'updated_at'
    ];

    protected array $searchableFields = [
        'name',
        'description',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }
/**
     * The properties associated with the service.
     */
    public function properties()
    {
        return $this->belongsToMany(Property::class, 'property_service')
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
}
