<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Resident extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory, Filterable;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'age' => 'integer',
        'password' => 'hashed',
    ];

    /* Relationships */
    public function properties()
    {
        return $this->belongsToMany(Property::class)
            ->using(PropertyResident::class)
            ->withPivot([
                'relationship_type',
                'sale_price',
                'ownership_share',
                'monthly_rent',
                'start_date',
                'end_date',
            ])
            ->withTimestamps();
    }

    /**
     * Get the admin who created this resident
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /**
     * Get all bills for this resident.
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * Get all payments made by this resident.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get all payment methods for this resident.
     */
    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    /**
     * Get the default payment method for this resident.
     */
    public function defaultPaymentMethod()
    {
        return $this->paymentMethods()->where('is_default', true)->first();
    }

    /**
     * Get properties where this resident is a buyer or co-buyer.
     */
    public function ownedProperties()
    {
        return $this->properties()->wherePivotIn('relationship_type', ['buyer', 'co_buyer']);
    }

    /**
     * Get properties where this resident is a renter.
     */
    public function rentedProperties()
    {
        return $this->properties()->where('relationship_type', 'renter');
    }

    /**
     * Get total unpaid bills for this resident.
     */
    public function getUnpaidBillsTotalAttribute(): float
    {
        return $this->bills()->unpaid()->sum('amount');
    }

    /**
     * Get overdue bills for this resident.
     */
    public function getOverdueBillsAttribute()
    {
        return $this->bills()->overdue()->get();
    }
}
