<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class House extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_occupied' => 'boolean',
        'number_of_residents' => 'integer',
    ];

    /**
     * Get the residents living in this house
     */
    public function residents(): HasMany
    {
        return $this->hasMany(Resident::class);
    }
}
