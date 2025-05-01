<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'base_price' => $this->base_price,
            'currency' => $this->currency,
            'unit_of_measure' => $this->unit_of_measure,
            'is_recurring' => $this->is_recurring,
            'recurrence' => $this->recurrence,
            'is_active' => $this->is_active,
            'active_requests_count' => $this->when(
                isset($this->active_requests_count),
                $this->active_requests_count
            ),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
