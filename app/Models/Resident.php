<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
}
