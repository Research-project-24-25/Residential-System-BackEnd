<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingRequest extends Model
{
    use HasFactory, Filterable;

    protected $fillable = [
        'user_id',
        'property_id',
        'requested_date',
        'purpose',
        'notes',
        'id_document',
        'status',
        'approved_date',
        'admin_id',
        'admin_notes',
    ];

    protected $casts = [
        'requested_date' => 'datetime',
        'approved_date' => 'datetime',
    ];

    protected array $filterableFields = [
        'status',
        'requested_date',
        'approved_date',
        'property_id',
        'user_id',
        'created_at',
        'updated_at'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
