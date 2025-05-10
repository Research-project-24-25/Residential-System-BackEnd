<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $data = [
      'id' => $this->id,
      'label' => $this->label,
      'type' => $this->type,
      'price' => $this->price,
      'currency' => $this->currency,
      'status' => $this->status,
      'description' => $this->description,
      'occupancy_limit' => $this->occupancy_limit,
      'bedrooms' => $this->bedrooms,
      'bathrooms' => $this->bathrooms,
      'area' => $this->area,
      'images' => $this->images,
      'features' => $this->features,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];

    return $data;
  }
}
