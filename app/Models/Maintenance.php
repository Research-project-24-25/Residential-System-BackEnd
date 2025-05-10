<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Maintenance extends Model
{
    use HasFactory, Filterable;

    protected $fillable = [
        'name',
        'description',
        'category',
        'estimated_cost',
        'currency',
        'estimated_hours',
        'is_active'
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'estimated_hours' => 'integer',
        'is_active' => 'boolean',
    ];

    protected array $filterableFields = [
        'category',
        'is_active',
        'estimated_cost',
        'estimated_hours',
        'created_at',
        'updated_at'
    ];

    protected array $searchableFields = [
        'name',
        'description',
    ];

    /**
     * Get all maintenance requests for this maintenance type.
     */
    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    /**
     * Get all active requests for this maintenance type.
     */
    public function activeRequests()
    {
        return $this->maintenanceRequests()
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Scope a query to only include active maintenance types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeOfCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}