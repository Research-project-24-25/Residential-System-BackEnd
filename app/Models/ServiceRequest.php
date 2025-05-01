<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequest extends Model
{
    use HasFactory, Filterable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'requested_date' => 'date',
        'scheduled_date' => 'date',
        'completion_date' => 'date',
        'estimated_cost' => 'decimal:2',
        'final_cost' => 'decimal:2'
    ];

    /**
     * Define the filterable fields for this model.
     *
     * @var array<int, string>
     */
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

    /**
     * Define the searchable fields for this model.
     *
     * @var array<int, string>
     */
    protected array $searchableFields = [
        'description',
        'notes',
    ];

    /**
     * Get the service associated with the request.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the property associated with the request.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the resident who created the request.
     */
    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    /**
     * Get the admin who handled the request.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Get the bill associated with this service request.
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * Check if the service request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the service request is approved but not yet scheduled.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the service request is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    /**
     * Check if the service request is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if the service request is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the service request is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if the service request has an associated bill.
     */
    public function hasBill(): bool
    {
        return $this->bill_id !== null;
    }

    /**
     * Scope a query to only include pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include active (non-completed, non-cancelled) requests.
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Scope a query to only include requests for a specific property.
     */
    public function scopeForProperty($query, $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    /**
     * Scope a query to only include requests from a specific resident.
     */
    public function scopeFromResident($query, $residentId)
    {
        return $query->where('resident_id', $residentId);
    }
}
