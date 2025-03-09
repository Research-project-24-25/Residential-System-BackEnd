<?php

namespace App\Http\Resources;

use App\Models\Apartment;
use App\Models\House;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    // Common properties for both types
    $data = [
      'id' => $this->id,
      'type' => $this->resource instanceof Apartment ? 'apartment' : 'house',
      'property_number' => $this->resource instanceof Apartment ? $this->apartment_number : $this->house_number,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];

    // Add apartment-specific data
    if ($this->resource instanceof Apartment) {
      $data['building'] = [
        'id' => $this->floor->building->id,
        'name' => $this->floor->building->name,
      ];
      $data['floor'] = [
        'id' => $this->floor->id,
        'number' => $this->floor->floor_number,
      ];
    }

    // Add resident count for both types
    $data['resident_count'] = $this->residents->count();

    return $data;
  }
}
