<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PropertyService extends Pivot
{
    use HasFactory;

    protected $table = 'property_service';

    protected $fillable = [
        'property_id',
        'service_id',
        'billing_type',
        'price',
        'status',
        'details',
        'activated_at',
        'expires_at',
        'last_billed_at',
    ];

    protected $casts = [
        'details' => 'array',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_billed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
