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

    /**
     * Get the user who created this meeting request.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_email', 'email');
    }

    /**
     * Get the property associated with this meeting request.
     */
    public function property()
    {
        if ($this->property_type === 'apartment') {
            return $this->belongsTo(Apartment::class, 'property_id');
        }

        if ($this->property_type === 'house') {
            return $this->belongsTo(House::class, 'property_id');
        }

        return null;
    }

    /**
     * Scope a query to only include upcoming meetings.
     */
    public function scopeUpcoming($query)
    {
        return $query->whereIn('status', ['pending', 'scheduled'])
            ->where('preferred_time', '>', now())
            ->orderBy('preferred_time');
    }

    /**
     * Check if the meeting request is cancellable.
     */
    public function isCancellable()
    {
        return !in_array($this->status, ['cancelled', 'completed']);
    }
}
