<?php

namespace App\Http\Controllers;

use App\Http\Requests\PropertyResidentRequest;
use App\Http\Resources\PropertyResidentResource;
use App\Models\Property;
use App\Models\PropertyResident;
use App\Models\Resident;
use App\Services\PropertyResidentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Throwable;

class PropertyResidentController extends Controller
{
    public function __construct(private PropertyResidentService $service) {}

    /**
     * Get all residents for a specific property
     */
    public function propertyResidents(int $propertyId, Request $request): ResourceCollection|JsonResponse
    {
        try {
            $property = Property::findOrFail($propertyId);

            $residents = $property->residents()
                ->withPivot([
                    'relationship_type',
                    'sale_price',
                    'ownership_share',
                    'monthly_rent',
                    'start_date',
                    'end_date',
                    'created_at',
                    'updated_at'
                ])
                ->paginate($request->get('per_page', 10));

            return PropertyResidentResource::collection($residents);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get all properties for a specific resident
     */
    public function residentProperties(int $residentId, Request $request): ResourceCollection|JsonResponse
    {
        try {
            $resident = Resident::findOrFail($residentId);

            $properties = $resident->properties()
                ->withPivot([
                    'relationship_type',
                    'sale_price',
                    'ownership_share',
                    'monthly_rent',
                    'start_date',
                    'end_date',
                    'created_at',
                    'updated_at'
                ])
                ->paginate($request->get('per_page', 10));

            return PropertyResidentResource::collection($properties);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Attach a resident to a property
     */
    public function attach(PropertyResidentRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $property = Property::findOrFail($validated['property_id']);
            $resident = Resident::findOrFail($validated['resident_id']);

            // Check if relationship already exists
            $existingRelationship = $property->residents()
                ->where('resident_id', $resident->id)
                ->exists();

            if ($existingRelationship) {
                return $this->errorResponse('Resident is already attached to this property', 422);
            }

            $pivotData = [
                'relationship_type' => $validated['relationship_type'],
                'sale_price' => $validated['sale_price'] ?? null,
                'ownership_share' => $validated['ownership_share'] ?? null,
                'monthly_rent' => $validated['monthly_rent'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
            ];

            $this->service->updatePropertyRelationship($resident, $property, $pivotData);

            $resident->load(['properties' => function ($query) use ($property) {
                $query->where('property_id', $property->id);
            }]);

            return $this->createdResponse(
                'Resident attached to property successfully',
                new PropertyResidentResource($resident->properties->first())
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update property-resident relationship
     */
    public function update(PropertyResidentRequest $request, int $propertyId, int $residentId): JsonResponse
    {
        try {
            $property = Property::findOrFail($propertyId);
            $resident = Resident::findOrFail($residentId);

            // Check if relationship exists
            $relationship = $property->residents()
                ->where('resident_id', $resident->id)
                ->first();

            if (!$relationship) {
                return $this->notFoundResponse('Property-resident relationship not found');
            }

            $validated = $request->validated();
            $pivotData = [
                'relationship_type' => $validated['relationship_type'],
                'sale_price' => $validated['sale_price'] ?? null,
                'ownership_share' => $validated['ownership_share'] ?? null,
                'monthly_rent' => $validated['monthly_rent'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
            ];

            $this->service->updatePropertyRelationship($resident, $property, $pivotData);

            $resident->load(['properties' => function ($query) use ($property) {
                $query->where('property_id', $property->id);
            }]);

            return $this->successResponse(
                'Property-resident relationship updated successfully',
                new PropertyResidentResource($resident->properties->first())
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Detach a resident from a property
     */
    public function detach(int $propertyId, int $residentId): JsonResponse
    {
        try {
            $property = Property::findOrFail($propertyId);
            $resident = Resident::findOrFail($residentId);

            $wasDetached = $this->service->removePropertyRelationship($resident, $property);

            if (!$wasDetached) {
                return $this->notFoundResponse('Property-resident relationship not found');
            }

            return $this->successResponse('Resident detached from property successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Show specific property-resident relationship
     */
    public function show(int $propertyId, int $residentId): JsonResponse
    {
        try {
            $property = Property::findOrFail($propertyId);

            $resident = $property->residents()
                ->where('resident_id', $residentId)
                ->withPivot([
                    'relationship_type',
                    'sale_price',
                    'ownership_share',
                    'monthly_rent',
                    'start_date',
                    'end_date',
                    'created_at',
                    'updated_at'
                ])
                ->first();

            if (!$resident) {
                return $this->notFoundResponse('Property-resident relationship not found');
            }

            return $this->successResponse(
                'Property-resident relationship retrieved successfully',
                new PropertyResidentResource($resident)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get all property-resident relationships with filters
     */
    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $query = PropertyResident::query()
                ->with(['property', 'resident']);

            // Apply filters
            if ($request->has('relationship_type')) {
                $query->where('relationship_type', $request->relationship_type);
            }

            if ($request->has('property_id')) {
                $query->where('property_id', $request->property_id);
            }

            if ($request->has('resident_id')) {
                $query->where('resident_id', $request->resident_id);
            }

            $relationships = $query->paginate($request->get('per_page', 10));

            return PropertyResidentResource::collection($relationships);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function restore(int $propertyId, int $residentId): JsonResponse
    {
        try {
            $relationship = PropertyResident::onlyTrashed()
                ->where('property_id', $propertyId)
                ->where('resident_id', $residentId)
                ->firstOrFail();

            $relationship->restore();

            return $this->successResponse('Property-resident relationship restored successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function forceDelete(int $propertyId, int $residentId): JsonResponse
    {
        try {
            $relationship = PropertyResident::onlyTrashed()
                ->where('property_id', $propertyId)
                ->where('resident_id', $residentId)
                ->firstOrFail();

            $relationship->forceDelete();

            return $this->successResponse('Property-resident relationship permanently deleted');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
