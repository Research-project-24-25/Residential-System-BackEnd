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
      'property_number' => $this->resource instanceof Apartment ? $this->number : $this->identifier,
      'price' => $this->price,
      'currency' => $this->currency,
      'price_type' => $this->price_type,
      'status' => $this->status,
      'description' => $this->description,
      'bedrooms' => $this->bedrooms,
      'bathrooms' => $this->bathrooms,
      'area' => $this->area,
      'images' => $this->images,
      'features' => $this->features,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];

    // Add apartment-specific data
    if ($this->resource instanceof Apartment) {
      $data['building'] = [
        'id' => $this->floor->building->id,
        'name' => $this->floor->building->identifier,
      ];
      $data['floor'] = [
        'id' => $this->floor->id,
        'number' => $this->floor->number,
      ];
    } else {
      // Add house-specific data
      $data['lot_size'] = $this->lot_size;
      $data['property_style'] = $this->property_style;
    }

    // Add resident count for both types
    $data['resident_count'] = $this->residents->count();

    return $data;
  }
}
