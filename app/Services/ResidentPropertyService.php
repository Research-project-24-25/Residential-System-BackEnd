<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Resident;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;
use App\Exceptions\PropertyRelationshipException;

class ResidentPropertyService
{
    /**
     * Create a resident and attach to property
     * 
     * @param array $residentData
     * @param Property $property
     * @param array $pivotData
     * @return Resident
     */
    public function createAndAttach(array $residentData, Property $property, array $pivotData): Resident
    {
        return DB::transaction(function () use ($residentData, $property, $pivotData) {
            // Hash password if not already hashed
            if (isset($residentData['password']) && !str_starts_with($residentData['password'], '$2y$')) {
                $residentData['password'] = Hash::make($residentData['password']);
            }

            // Create new resident
            $resident = Resident::create($residentData);

            // Attach property with pivot data
            $property->residents()->attach($resident->id, $pivotData);

            // Update property status automatically
            $this->updatePropertyStatus($property, $pivotData['relationship_type']);

            return $resident->load('properties');
        });
    }

    /**
     * Update resident's property relationship
     * 
     * @param Resident $resident
     * @param Property $property
     * @param array $pivotData
     * @return Resident
     */
    public function updatePropertyRelationship(Resident $resident, Property $property, array $pivotData): Resident
    {
        return DB::transaction(function () use ($resident, $property, $pivotData) {
            // Check if resident is already related to this property
            $exists = $resident->properties()->where('property_id', $property->id)->exists();

            if ($exists) {
                // Update existing relationship
                $resident->properties()->updateExistingPivot($property->id, $pivotData);
            } else {
                // Create new relationship
                $resident->properties()->attach($property->id, $pivotData);
            }

            // Update property status automatically
            $this->updatePropertyStatus($property, $pivotData['relationship_type']);

            return $resident->load('properties');
        });
    }

    /**
     * Remove resident's property relationship
     * 
     * @param Resident $resident
     * @param Property $property
     * @return bool
     */
    public function removePropertyRelationship(Resident $resident, Property $property): bool
    {
        return DB::transaction(function () use ($resident, $property) {
            // Detach the property
            $detached = $resident->properties()->detach($property->id);

            // Check if property has any more residents
            $hasResidents = $property->residents()->exists();

            // If no more residents, reset property status to available
            if (!$hasResidents) {
                $property->update(['status' => 'available_now']);
            }

            return $detached > 0;
        });
    }

    /**
     * Update property status based on resident relationship type
     * 
     * @param Property $property
     * @param string $relationshipType
     * @return void
     */
    protected function updatePropertyStatus(Property $property, string $relationshipType): void
    {
        $status = match ($relationshipType) {
            'buyer', 'co_buyer' => 'sold',
            'renter' => 'rented',
            default => $property->status,
        };

        $property->update(['status' => $status]);
    }
}
