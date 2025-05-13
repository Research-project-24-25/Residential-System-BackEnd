<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, Filterable, SoftDeletes;

    protected $fillable = [
        'bill_id',
        'resident_id',
        'amount',
        'currency',
        'status',
        'transaction_id',
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

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'processed_by');
    }

    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
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
