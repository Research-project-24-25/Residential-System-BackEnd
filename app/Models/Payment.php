<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, Filterable;

    protected $fillable = [
        'bill_id',
        'resident_id',
        'payment_method_id',
        'amount',
        'currency',
        'status',
        'transaction_id',
        'receipt_url',
        'payment_date',
        'notes',
        'metadata',
        'processed_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Define the filterable fields for this model.
     */
    protected array $filterableFields = [
        'bill_id',
        'resident_id',
        'payment_method_id',
        'status',
        'payment_date',
        'amount',
        'created_at',
        'updated_at'
    ];

    /**
     * Define the searchable fields for this model.
     */
    protected array $searchableFields = [
        'transaction_id',
        'notes',
    ];

    /**
     * Get the bill that this payment is for.
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * Get the resident that made the payment.
     */
    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    /**
     * Get the payment method used for this payment.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Get the admin who processed the payment.
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'processed_by');
    }

    /**
     * Scope a query to only include payments with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include completed payments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include payments for a specific resident.
     */
    public function scopeForResident($query, $residentId)
    {
        return $query->where('resident_id', $residentId);
    }

    /**
     * Scope a query to only include payments within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }
}
