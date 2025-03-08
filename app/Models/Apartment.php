<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Apartment extends Model
{
    protected $guarded = ['id'];

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }
}
