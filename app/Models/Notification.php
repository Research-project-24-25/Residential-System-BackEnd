<?php

namespace App\Models;

use Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $appends = [
        'unread_count',
        'read_count',
        'total_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];



    /**
     * Get the notifiable entity that the notification belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Mark the notification as read.
     *
     * @return void
     */
    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->forceFill(['read_at' => $this->freshTimestamp()])->save();
        }
    }

    /**
     * Mark the notification as unread.
     *
     * @return void
     */
    public function markAsUnread(): void
    {
        if (! is_null($this->read_at)) {
            $this->forceFill(['read_at' => null])->save();
        }
    }

    /**
     * Determine if a notification has been read.
     *
     * @return bool
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Determine if a notification has not been read.
     *
     * @return bool
     */
    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    /**
     * Scope a query to only include read notifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope a query to only include unread notifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Get the count of unread notifications
     * 
     * @return Attribute
     */
    public function getUnreadCountAttribute(): Attribute
    {
        return Attribute::make(
            get: fn () => static::whereNull('read_at')
                ->where('notifiable_type', $this->notifiable_type)
                ->where('notifiable_id', $this->notifiable_id)
                ->count()
        );
    }

    /**
     * Get the count of read notifications
     * 
     * @return Attribute
     */
    public function getReadCountAttribute(): Attribute
    {
        return Attribute::make(
            get: fn () => static::whereNotNull('read_at')
                ->where('notifiable_type', $this->notifiable_type)
                ->where('notifiable_id', $this->notifiable_id)
                ->count()
        );
    }

    /**
     * Get the total count of notifications
     * 
     * @return Attribute
     */
    public function getTotalCountAttribute(): Attribute
    {
        return Attribute::make(
            get: fn () => static::where('notifiable_type', $this->notifiable_type)
                ->where('notifiable_id', $this->notifiable_id)
                ->count()
        );
    }
}
