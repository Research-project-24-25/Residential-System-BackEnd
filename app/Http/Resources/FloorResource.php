<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FloorResource extends JsonResource
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
            'building_id' => $this->building_id,
            'floor_number' => $this->floor_number,
            'total_apartments' => $this->total_apartments,
            'building' => new BuildingResource($this->whenLoaded('building')),
            'apartments' => ApartmentResource::collection($this->whenLoaded('apartments')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
