<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PropertyResident extends Pivot
{
    use HasFactory;
    protected $table = 'property_resident';

    protected $fillable = [
        'property_id',
        'resident_id',
        'relationship_type',
        'sale_price',
        'ownership_share',
        'monthly_rent',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date'      => 'date',
        'end_date'        => 'date',
        'sale_price'      => 'decimal:2',
        'monthly_rent'    => 'decimal:2',
        'ownership_share' => 'decimal:2',
    ];
}
