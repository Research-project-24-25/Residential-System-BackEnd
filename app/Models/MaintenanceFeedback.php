<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceFeedback extends Model
{
    use HasFactory, Filterable, SoftDeletes;

    protected $fillable = [
        'maintenance_request_id',
        'resident_id',
        'rating',
        'comments',
        'improvement_suggestions',
        'resolved_satisfactorily',
        'would_recommend'
    ];

    protected $casts = [
        'rating' => 'integer',
        'resolved_satisfactorily' => 'boolean',
        'would_recommend' => 'boolean'
    ];

    protected array $filterableFields = [
        'rating',
        'resolved_satisfactorily',
        'would_recommend',
        'created_at',
        'updated_at'
    ];

    protected array $searchableFields = [
        'comments',
        'improvement_suggestions'
    ];

    /**
     * Get the maintenance request associated with this feedback.
     */
    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    /**
     * Get the resident who provided the feedback.
     */
    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    /**
     * Scope a query to only include positive feedback (rating >= 4).
     */
    public function scopePositive($query)
    {
        return $query->where('rating', '>=', 4);
    }

    /**
     * Scope a query to only include negative feedback (rating <= 2).
     */
    public function scopeNegative($query)
    {
        return $query->where('rating', '<=', 2);
    }

    /**
     * Scope a query to only include neutral feedback (rating = 3).
     */
    public function scopeNeutral($query)
    {
        return $query->where('rating', 3);
    }

    /**
     * Scope a query to only include satisfied resolutions.
     */
    public function scopeSatisfied($query)
    {
        return $query->where('resolved_satisfactorily', true);
    }

    /**
     * Scope a query to only include unsatisfied resolutions.
     */
    public function scopeUnsatisfied($query)
    {
        return $query->where('resolved_satisfactorily', false);
    }
}
