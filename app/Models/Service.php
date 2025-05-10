<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function activeRequests()
    {
        return $this->serviceRequests()
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

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
}
