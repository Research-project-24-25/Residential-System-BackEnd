<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class MeetingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_type',
        'property_id',
        'user_name',
        'user_email',
        'user_phone',
        'preferred_time',
        'message',
        'status',
        'verification_token',
        'verified_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'preferred_time' => 'datetime',
        'verified_at' => 'datetime',
    ];
}
