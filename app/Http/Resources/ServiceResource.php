<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Only administrators can see inactive services
        if ($request->user() && $request->user()->getTable() !== 'admins' && !$this->is_active) {
            return [];
        }

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
            'properties_count' => $this->when(
                isset($this->properties_count),
                $this->properties_count
            ),
        ];
    }
}
