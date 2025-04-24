<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResidentResource extends JsonResource
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
            'username' => $this->username,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'age' => $this->age,
            'gender' => $this->gender,
            'properties' => $this->whenLoaded('properties', function () {
                return $this->properties->map(function ($property) {
                    $pivotData = $property->pivot;

                    return [
                        'id' => $property->id,
                        'label' => $property->label,
                        'type' => $property->type,
                        'status' => $property->status,
                        'relationship' => [
                            'type' => $pivotData->relationship_type,
                            'sale_price' => $pivotData->sale_price,
                            'ownership_share' => $pivotData->ownership_share,
                            'monthly_rent' => $pivotData->monthly_rent,
                            'start_date' => $pivotData->start_date,
                            'end_date' => $pivotData->end_date,
                        ]
                    ];
                });
            }),
            'created_by' => $this->whenLoaded('createdBy', fn() => $this->createdBy->email),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
