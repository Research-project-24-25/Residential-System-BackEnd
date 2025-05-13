<?php

namespace App\Http\Resources;

use App\Traits\ResourceHelpers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceRequestResource extends JsonResource
{
    use ResourceHelpers;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'maintenance' => $this->whenLoaded('maintenance', function () {
                return $this->handleRelation($this->maintenance, function ($maintenance) {
                    return [
                        'id' => $maintenance->id,
                        'name' => $maintenance->name,
                        'category' => $maintenance->category,
                    ];
                });
            }),
            'property' => $this->whenLoaded('property', function () {
                return $this->handleRelation($this->property, function ($property) {
                    return [
                        'id' => $property->id,
                        'label' => $property->label,
                        'type' => $property->type,
                    ];
                });
            }),
            'resident' => $this->whenLoaded('resident', function () {
                return $this->handleRelation($this->resident, function ($resident) {
                    return [
                        'id' => $resident->id,
                        'name' => $resident->name,
                        'email' => $resident->email,
                    ];
                });
            }),
            'admin' => $this->whenLoaded('admin', function () {
                return $this->handleRelation($this->admin, function ($admin) {
                    return [
                        'id' => $admin->id,
                        'name' => $admin->name ?? $admin->username,
                        'email' => $admin->email,
                    ];
                });
            }),
            'bill' => $this->whenLoaded('bill', function () {
                return $this->handleRelation($this->bill, function ($bill) {
                    return [
                        'id' => $bill->id,
                        'amount' => $bill->amount,
                        'status' => $bill->status,
                        'due_date' => $bill->due_date->format('Y-m-d'),
                    ];
                });
            }),
            'feedback' => $this->whenLoaded('feedback', function () {
                return $this->handleRelation($this->feedback, fn($feedback) => new MaintenanceFeedbackResource($feedback));
            }),
            'maintenance_id' => $this->maintenance_id,
            'property_id' => $this->property_id,
            'resident_id' => $this->resident_id,
            'description' => $this->description,
            'issue_details' => $this->issue_details,
            'images' => $this->images,
            'priority' => $this->priority,
            'status' => $this->status,
            'requested_date' => $this->requested_date->format('Y-m-d'),
            'scheduled_date' => $this->scheduled_date ? $this->scheduled_date->format('Y-m-d') : null,
            'completion_date' => $this->completion_date ? $this->completion_date->format('Y-m-d') : null,
            'notes' => $this->notes,
            'estimated_cost' => $this->estimated_cost,
            'final_cost' => $this->final_cost,
            'actual_cost' => $this->actual_cost,
            'bill_id' => $this->bill_id,
            'has_feedback' => $this->has_feedback,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'deleted_at' => $this->when($this->deleted_at, $this->deleted_at?->format('Y-m-d H:i:s')),
        ];
    }
}
