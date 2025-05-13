<?php

namespace App\Http\Resources;

use App\Traits\ResourceHelpers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceFeedbackResource extends JsonResource
{
    use ResourceHelpers;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'maintenance_request_id' => $this->maintenance_request_id,
            'maintenance_request' => $this->whenLoaded('maintenanceRequest', function () {
                return $this->handleRelation($this->maintenanceRequest, function ($maintenanceRequest) {
                    return [
                        'id' => $maintenanceRequest->id,
                        'description' => $maintenanceRequest->description,
                        'status' => $maintenanceRequest->status,
                    ];
                });
            }),
            'resident' => $this->whenLoaded('resident', function () {
                return $this->handleRelation($this->resident, function ($resident) {
                    return [
                        'id' => $resident->id,
                        'name' => $resident->name,
                    ];
                });
            }),
            'rating' => $this->rating,
            'comments' => $this->comments,
            'improvement_suggestions' => $this->improvement_suggestions,
            'resolved_satisfactorily' => $this->resolved_satisfactorily,
            'would_recommend' => $this->would_recommend,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'deleted_at' => $this->when($this->deleted_at, $this->deleted_at?->format('Y-m-d H:i:s')),
        ];
    }
}
