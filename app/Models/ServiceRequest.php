<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequest extends Model
{
    use HasFactory, Filterable;

    protected $fillable = [
        'service_id',
        'property_id',
        'resident_id',
        'description',
        'requested_date',
        'scheduled_date',
        'completion_date',
        'status',
        'notes',
        'admin_id',
        'estimated_cost',
        'final_cost',
        'bill_id'
    ];

    protected $casts = [
        'requested_date' => 'date',
        'scheduled_date' => 'date',
        'completion_date' => 'date',
        'estimated_cost' => 'decimal:2',
        'final_cost' => 'decimal:2'
    ];

    protected array $filterableFields = [
        'service_id',
        'property_id',
        'resident_id',
        'status',
        'requested_date',
        'scheduled_date',
        'completion_date',
        'admin_id',
        'created_at',
        'updated_at'
    ];

    protected array $searchableFields = [
        'description',
        'notes',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function hasBill(): bool
    {
        return $this->bill_id !== null;
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeForProperty($query, $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeFromResident($query, $residentId)
    {
        return $query->where('resident_id', $residentId);
    }
}
