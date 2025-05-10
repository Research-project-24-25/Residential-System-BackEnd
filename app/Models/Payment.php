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

    protected array $searchableFields = [
        'transaction_id',
        'notes',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'processed_by');
    }

    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForResident($query, $residentId)
    {
        return $query->where('resident_id', $residentId);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }
}
