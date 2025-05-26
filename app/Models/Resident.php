<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use App\Notifications\ResetPassword;

class Resident extends Authenticatable implements CanResetPasswordContract
{
    use HasApiTokens, Notifiable, HasFactory, Filterable, SoftDeletes, CanResetPassword;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'age' => 'integer',
        'password' => 'hashed',
    ];

    protected array $filterableFields = [
        'first_name',
        'last_name',
        'gender',
        'age',
        'created_by',
        'created_at',
        'updated_at'
    ];

    protected array $searchableFields = [
        'username',
        'first_name',
        'last_name',
        'email',
        'phone_number'
    ];

    public function getNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getProfileImageAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        return asset('storage/' . $value);
    }

    public function setProfileImageAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['profile_image'] = null;
            return;
        }

        // Remove the asset('storage/') prefix if it exists
        $prefix = asset('storage/');
        if (is_string($value) && str_starts_with($value, $prefix)) {
            $this->attributes['profile_image'] = substr($value, strlen($prefix));
        } else {
            $this->attributes['profile_image'] = $value; // Store as is if prefix not found or not a string
        }
    }

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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function ownedProperties()
    {
        return $this->properties()->wherePivotIn('relationship_type', ['buyer', 'co_buyer']);
    }

    public function rentedProperties()
    {
        return $this->properties()->where('relationship_type', 'renter');
    }

    public function getUnpaidBillsTotalAttribute(): float
    {
        return $this->bills()->unpaid()->sum('amount');
    }

    public function getOverdueBillsAttribute()
    {
        return $this->bills()->overdue()->get();
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }
}
