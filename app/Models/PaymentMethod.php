<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use HasFactory, Filterable;

    protected $fillable = [
        'resident_id',
        'type',
        'provider',
        'account_number',
        'last_four',
        'expiry_date',
        'cardholder_name',
        'is_default',
        'is_verified',
        'status',
        'metadata'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_verified' => 'boolean',
        'expiry_date' => 'date',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'account_number',
    ];

    /**
     * Define the filterable fields for this model.
     */
    protected array $filterableFields = [
        'resident_id',
        'type',
        'provider',
        'status',
        'is_default',
        'is_verified',
        'created_at',
        'updated_at'
    ];

    /**
     * Get the resident that owns the payment method.
     */
    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    /**
     * Get the payments made with this payment method.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the masked account number.
     */
    public function getMaskedAccountNumberAttribute(): string
    {
        if (empty($this->account_number)) {
            return '';
        }

        $visible = $this->last_four ?? substr($this->account_number, -4);
        $masked = str_repeat('*', strlen($this->account_number) - strlen($visible));

        return $masked . $visible;
    }

    /**
     * Scope a query to only include payment methods for a specific resident.
     */
    public function scopeForResident($query, $residentId)
    {
        return $query->where('resident_id', $residentId);
    }

    /**
     * Scope a query to only include active payment methods.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
