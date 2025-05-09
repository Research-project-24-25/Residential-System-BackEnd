<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceFeedbackResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'maintenance_request_id' => $this->maintenance_request_id,
            'maintenance_request' => $this->whenLoaded('maintenanceRequest', function () {
                return [
                    'id' => $this->maintenanceRequest->id,
                    'description' => $this->maintenanceRequest->description,
                    'status' => $this->maintenanceRequest->status,
                ];
            }),
            'resident' => $this->whenLoaded('resident', function () {
                return [
                    'id' => $this->resident->id,
                    'name' => $this->resident->name,
                ];
            }),
            'rating' => $this->rating,
            'comments' => $this->comments,
            'improvement_suggestions' => $this->improvement_suggestions,
            'resolved_satisfactorily' => $this->resolved_satisfactorily,
            'would_recommend' => $this->would_recommend,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
