<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyServiceResource extends JsonResource
{
    public function toArray($request): array
    {
        $resourceType = $this->getResourceType();
        $pivotData = $this->whenPivotLoaded('property_service', function () {
           
            return [
                'billing_type' => $this->pivot->billing_type,
                'price' => $this->pivot->price,
                'status' => $this->pivot->status,
                'details' => json_decode($this->pivot->details),
                'activated_at' => $this->pivot->activated_at,
                'expires_at' => $this->pivot->expires_at,
                'last_billed_at' => $this->pivot->last_billed_at,
                'created_at' => $this->pivot->created_at,
                'updated_at' => $this->pivot->updated_at,
            ];
        });

        if ($resourceType === 'service') {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'description' => $this->description,
                'type' => $this->type,
                'base_price' => $this->base_price,
                'currency' => $this->currency,
                'unit_of_measure' => $this->unit_of_measure,
                'is_recurring' => $this->is_recurring,
                'recurrence' => $this->recurrence,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
                'pivot' => $pivotData,
            ];
        } else {
            return [
                'id' => $this->id,
                'label' => $this->label,
                'type' => $this->type,
                'status' => $this->status,
                'description' => $this->description,
                'occupancy_limit' => $this->occupancy_limit,
                'bedrooms' => $this->bedrooms,
                'bathrooms' => $this->bathrooms,
                'area' => $this->area,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
                'pivot' => $pivotData,
            ];
        }
    }

    /**
     * Determine if the resource is a Service or Property.
     */
    private function getResourceType(): string
    {
        // Check for properties that are unique to each model
        if (isset($this->label)) {
            return 'property';
        }

        return 'service';
    }
}
