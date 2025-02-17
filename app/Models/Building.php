<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    protected $guarded = [];

    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class);
    }
}
