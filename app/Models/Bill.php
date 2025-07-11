<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bill extends Model
{
    use HasFactory, Filterable, SoftDeletes;

    protected $fillable = [
        'property_id',
        'resident_id',
        'bill_type',
        'amount',
        'due_date',
        'description',
        'status',
        'recurrence',
        'next_billing_date',
        'created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'next_billing_date' => 'date',
    ];

    protected array $filterableFields = [
        'bill_type',
        'status',
        'due_date',
        'amount',
        'recurrence',
        'next_billing_date',
        'created_by',
        'created_at',
        'updated_at'
    ];

    protected array $searchableFields = [
        'description',
        'status',
        'bill_type',
        'amount'
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Calculate the amount paid for this bill, including soft-deleted payments.
     */
    public function getPaidAmountAttribute(): float
    {
        // Sum of positive payments that are currently marked as 'paid'.
        // Include soft-deleted payments to maintain accounting integrity.
        return $this->payments()
            ->withTrashed()
            ->where('status', 'paid')
            ->where('amount', '>', 0)
            ->sum('amount');
    }

    /**
     * Calculate the remaining balance for this bill.
     */
    public function getRemainingBalanceAttribute(): float
    {
        return $this->amount - $this->paid_amount;
    }

    public function getIsFullyPaidAttribute(): bool
    {
        return $this->remaining_balance <= 0;
    }

    public function getIsOverdueAttribute(): bool
    {
        return !$this->is_fully_paid && $this->due_date < now();
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', '!=', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->where('status', '!=', 'paid');
    }

    public function scopeForResident($query, $residentId)
    {
        return $query->where('resident_id', $residentId);
    }

    public function scopeForProperty($query, $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }
}
