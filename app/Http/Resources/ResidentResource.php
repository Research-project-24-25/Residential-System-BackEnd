<?php

namespace App\Http\Resources;

use App\Traits\ResourceHelpers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResidentResource extends JsonResource
{
    use ResourceHelpers;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $this->name, // Uses the accessor
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'age' => $this->age,
            'gender' => $this->gender,
            'profile_image' => $this->getRawOriginal('profile_image') ? asset('storage/' . $this->getRawOriginal('profile_image')) : null,
            'properties' => $this->whenLoaded('properties', function () {
                return $this->properties->map(function ($property) {
                    $pivotData = $property->pivot;
                    $propertyData = $this->handleRelation($property, function ($property) {
                        return [
                            'id' => $property->id,
                            'label' => $property->label,
                            'type' => $property->type,
                            'status' => $property->status,
                        ];
                    });

                    // Add relationship data if property exists
                    if ($propertyData) {
                        $propertyData['relationship'] = [
                            'type' => $pivotData->relationship_type,
                            'sale_price' => $pivotData->sale_price,
                            'ownership_share' => $pivotData->ownership_share,
                            'monthly_rent' => $pivotData->monthly_rent,
                            'start_date' => $pivotData->start_date,
                            'end_date' => $pivotData->end_date,
                        ];
                    }

                    return $propertyData;
                });
            }),
            'created_by' => $this->whenLoaded('createdBy', function () {
                return $this->handleRelation($this->createdBy, fn($admin) => $admin->email);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->when($this->deleted_at, $this->deleted_at),
        ];
    }
}
