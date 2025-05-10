<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service' => $this->whenLoaded('service', function () {
                return [
                    'id' => $this->service->id,
                    'name' => $this->service->name,
                    'type' => $this->service->type,
                    'base_price' => $this->service->base_price,
                ];
            }),
            'property' => $this->whenLoaded('property', function () {
                return [
                    'id' => $this->property->id,
                    'label' => $this->property->label,
                    'type' => $this->property->type,
                ];
            }),
            'resident' => $this->whenLoaded('resident', function () {
                return [
                    'id' => $this->resident->id,
                    'name' => $this->resident->name,
                    'email' => $this->resident->email,
                ];
            }),
            'admin' => $this->whenLoaded('admin', function () {
                return [
                    'id' => $this->admin->id,
                    'name' => $this->admin->name ?? $this->admin->username,
                    'email' => $this->admin->email,
                ];
            }),
            'bill' => $this->whenLoaded('bill', function () {
                return [
                    'id' => $this->bill->id,
                    'amount' => $this->bill->amount,
                    'status' => $this->bill->status,
                    'due_date' => $this->bill->due_date->format('Y-m-d'),
                ];
            }),
            'service_id' => $this->service_id,
            'property_id' => $this->property_id,
            'resident_id' => $this->resident_id,
            'description' => $this->description,
            'requested_date' => $this->requested_date->format('Y-m-d'),
            'scheduled_date' => $this->scheduled_date ? $this->scheduled_date->format('Y-m-d') : null,
            'completion_date' => $this->completion_date ? $this->completion_date->format('Y-m-d') : null,
            'status' => $this->status,
            'notes' => $this->notes,
            'estimated_cost' => $this->estimated_cost,
            'final_cost' => $this->final_cost,
            'bill_id' => $this->bill_id,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
