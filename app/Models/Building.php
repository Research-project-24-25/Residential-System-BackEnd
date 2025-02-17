<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class);
    }
}
