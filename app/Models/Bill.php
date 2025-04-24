<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    use HasFactory, Filterable;

    protected $fillable = [
        'property_id',
        'resident_id',
        'bill_type',
        'amount',
        'currency',
        'due_date',
        'description',
        'status',
        'recurrence',
        'next_billing_date',
        'metadata',
        'created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'next_billing_date' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Define the filterable fields for this model.
     */
    protected array $filterableFields = [
        'bill_type',
        'property_id',
        'resident_id',
        'status',
        'due_date',
        'amount',
        'created_at',
        'updated_at'
    ];

    /**
     * Define the searchable fields for this model.
     */
    protected array $searchableFields = [
        'description',
    ];

    /**
     * Get the property that the bill is for.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the resident that the bill is for.
     */
    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    /**
     * Get the admin who created the bill.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /**
     * Get the payments for this bill.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Calculate the amount paid for this bill.
     */
    public function getPaidAmountAttribute(): float
    {
        return $this->payments()
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Calculate the remaining balance for this bill.
     */
    public function getRemainingBalanceAttribute(): float
    {
        return $this->amount - $this->paid_amount;
    }

    /**
     * Check if the bill is fully paid.
     */
    public function getIsFullyPaidAttribute(): bool
    {
        return $this->remaining_balance <= 0;
    }

    /**
     * Check if the bill is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return !$this->is_fully_paid && $this->due_date < now();
    }

    /**
     * Scope a query to only include unpaid bills.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('status', '!=', 'paid');
    }

    /**
     * Scope a query to only include overdue bills.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->where('status', '!=', 'paid');
    }

    /**
     * Scope a query to only include bills for a specific resident.
     */
    public function scopeForResident($query, $residentId)
    {
        return $query->where('resident_id', $residentId);
    }

    /**
     * Scope a query to only include bills for a specific property.
     */
    public function scopeForProperty($query, $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }
}
