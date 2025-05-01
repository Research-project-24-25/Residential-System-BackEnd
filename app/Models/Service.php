<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory, Filterable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'base_price' => 'decimal:2',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Define the filterable fields for this model.
     *
     * @var array<int, string>
     */
    protected array $filterableFields = [
        'type',
        'is_recurring',
        'is_active',
        'base_price',
        'created_at',
        'updated_at'
    ];

    /**
     * Define the searchable fields for this model.
     *
     * @var array<int, string>
     */
    protected array $searchableFields = [
        'name',
        'description',
    ];

    /**
     * Get all service requests for this service.
     */
    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    /**
     * Get all active requests for this service.
     */
    public function activeRequests()
    {
        return $this->serviceRequests()
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Scope a query to only include active services.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
}
