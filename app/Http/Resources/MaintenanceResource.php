<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'estimated_cost' => $this->estimated_cost,
            'currency' => $this->currency,
            'estimated_hours' => $this->estimated_hours,
            'is_active' => $this->is_active,
            'active_requests_count' => $this->when(
                isset($this->active_requests_count),
                $this->active_requests_count
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
