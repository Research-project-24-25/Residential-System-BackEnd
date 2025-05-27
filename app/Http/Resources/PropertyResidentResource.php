<?php

namespace App\Http\Resources;

use App\Traits\ResourceHelpers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResidentResource extends JsonResource
{
    use ResourceHelpers;

    public function toArray(Request $request): array
    {
        $resourceType = $this->getResourceType();
        $pivotData = $this->whenPivotLoaded('property_resident', function () {
            return [
                'relationship_type' => $this->pivot->relationship_type,
                'sale_price' => $this->pivot->sale_price,
                'ownership_share' => $this->pivot->ownership_share,
                'monthly_rent' => $this->pivot->monthly_rent,
                'start_date' => $this->pivot->start_date,
                'end_date' => $this->pivot->end_date,
                'created_at' => $this->pivot->created_at,
                'updated_at' => $this->pivot->updated_at,
            ];
        });

        if ($resourceType === 'resident') {
            return [
                'id' => $this->id,
                'username' => $this->username,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'name' => $this->name,
                'email' => $this->email,
                'phone_number' => $this->phone_number,
                'age' => $this->age,
                'gender' => $this->gender,
                'profile_image' => $this->getRawOriginal('profile_image') ? asset('storage/' . $this->getRawOriginal('profile_image')) : null,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
                'relationship' => $pivotData,
            ];
        } else {
            return [
                'id' => $this->id,
                'label' => $this->label,
                'type' => $this->type,
                'price' => $this->price,
                'status' => $this->status,
                'description' => $this->description,
                'occupancy_limit' => $this->occupancy_limit,
                'bedrooms' => $this->bedrooms,
                'bathrooms' => $this->bathrooms,
                'area' => $this->area,
                'images' => $this->images ? array_map(fn($image) => asset('storage/' . $image), json_decode($this->getRawOriginal('images'), true) ?? []) : [],
                'features' => $this->features,
                'acquisition_cost' => $this->acquisition_cost,
                'acquisition_date' => $this->acquisition_date,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
                'relationship' => $pivotData,
            ];
        }
    }

    /**
     * Determine if the resource is a Resident or Property.
     */
    private function getResourceType(): string
    {
        // Check for properties that are unique to each model
        if (isset($this->username)) {
            return 'resident';
        }

        return 'property';
    }
}
