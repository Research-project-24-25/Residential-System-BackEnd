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

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getMaskedAccountNumberAttribute(): string
    {
        if (empty($this->account_number)) {
            return '';
        }

        $visible = $this->last_four ?? substr($this->account_number, -4);
        $masked = str_repeat('*', strlen($this->account_number) - strlen($visible));

        return $masked . $visible;
    }

    public function scopeForResident($query, $residentId)
    {
        return $query->where('resident_id', $residentId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
