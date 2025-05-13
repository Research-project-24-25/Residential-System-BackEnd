<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Resident;
use Illuminate\Support\Facades\DB;

class PropertyResidentService
{
    public function createAndAttach(array $residentData, Property $property, array $pivotData): Resident
    {
        // DB transaction to ensure data integrity, and rollback in case of failure
        return DB::transaction(function () use ($residentData, $property, $pivotData) {
            // Create new resident
            $resident = Resident::create($residentData);

            // Attach property with pivot data
            $property->residents()->attach($resident->id, $pivotData);

            // Update property status automatically
            $this->updatePropertyStatus($property, $pivotData['relationship_type']);

            return $resident->load('properties');
        });
    }

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

    public function removePropertyRelationship(Resident $resident, Property $property): bool
    {
        return DB::transaction(function () use ($resident, $property) {
            // Instead of detaching, update the pivot with deleted_at
            $pivotRecord = $resident->properties()->where('property_id', $property->id)->first()->pivot;
            if ($pivotRecord) {
                $pivotRecord->delete();
                $detached = 1; // To indicate operation was successful
            } else {
                $detached = 0;
            }

            // Check if property has any more residents
            $hasResidents = $property->residents()->exists();

            // If no more residents, reset property status to available
            if (!$hasResidents) {
                $property->update(['status' => 'available_now']);
            }

            return $detached > 0;
        });
    }

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
