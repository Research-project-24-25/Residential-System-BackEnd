<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'is_recurring' => 'boolean',
    ];

    protected array $filterableFields = [
        'type',
        'is_recurring',
        'base_price',
        'created_at',
        'updated_at'
    ];

    protected array $searchableFields = [
        'name',
        'description',
    ];

    /**
     * The properties associated with the service.
     */
    public function properties(): BelongsToMany
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

    /**
     * Properties with this service active
     */
    public function activeProperties(): BelongsToMany
    {
        return $this->properties()->wherePivot('status', 'active');
    }

    /**
     * Scope a query to only include recurring services.
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include services with a specific recurrence.
     */
    public function scopeWithRecurrence($query, $recurrence)
    {
        return $query->where('recurrence', $recurrence);
    }
}
