<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Apartment extends Model
{
    protected $guarded = [];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }
}
