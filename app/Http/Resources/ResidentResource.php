<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResidentResource extends JsonResource
{
    /**
     * 
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
            'status' => $this->status,
            'property_type' => $this->house_id ? 'house' : ($this->apartment_id ? 'apartment' : null),
            'house' => $this->when($this->house_id, function() {
                return new HouseResource($this->whenLoaded('house', function() {
                    return $this->house->loadCount('residents');
                }));
            }),
            'apartment' => $this->when($this->apartment_id, new ApartmentResource($this->whenLoaded('apartment'))),
            'created_by' => $this->whenLoaded('createdBy', fn() => $this->createdBy->email),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
