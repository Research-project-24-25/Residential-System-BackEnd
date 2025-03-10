<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Apartment extends Model
{
    use HasFactory;
    
    protected $guarded = ['id'];

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * Get the residents living in this apartment
     */
    public function residents(): HasMany
    {
        return $this->hasMany(Resident::class);
    }
}
