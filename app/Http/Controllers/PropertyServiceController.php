<?php

namespace App\Http\Controllers;

use App\Http\Requests\PropertyServiceRequest;
use App\Http\Resources\PropertyServiceResource;
use App\Models\Property;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Throwable;

class PropertyServiceController extends Controller
{
    /**
     * Get all services for a property.
     */
    public function propertyServices(int $propertyId, Request $request): ResourceCollection|JsonResponse
    {
        try {
            $property = Property::findOrFail($propertyId);
            $user = $request->user();

            // Check access permission
            if ($user->getTable() === 'residents') {
                $isResidentOfProperty = $property->residents()
                    ->where('resident_id', $user->id)
                    ->exists();

                if (!$isResidentOfProperty) {
                    return $this->forbiddenResponse('You do not have access to this property');
                }
            }

            $query = $property->services()->withPivot('billing_type', 'price', 'status', 'details', 'activated_at', 'expires_at', 'last_billed_at', 'created_at', 'updated_at');

            $services = $query->paginate($request->input('per_page', 10));

            return PropertyServiceResource::collection($services);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get all properties for a service.
     */
    public function serviceProperties(int $serviceId, Request $request): ResourceCollection|JsonResponse
    {
        try {
            // Only admins can access this endpoint
            if ($request->user()->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can access this resource');
            }

            $service = Service::findOrFail($serviceId);
            $properties = $service->properties()
                ->with(['pivot'])
                ->paginate($request->input('per_page', 10));

            return PropertyServiceResource::collection($properties);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Attach a service to a property.
     */
    public function attach(PropertyServiceRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $property = Property::findOrFail($validated['property_id']);
            $service = Service::findOrFail($validated['service_id']);

            // Prepare pivot data
            $pivotData = [
                'billing_type' => $validated['billing_type'],
                'price' => $validated['price'],
                'status' => $validated['status'] ?? 'inactive',
                'details' => $validated['details'] ?? null,
                'activated_at' => $validated['activated_at'] ?? null,
                'expires_at' => $validated['expires_at'] ?? null,
            ];

            // Attach the service to the property
            $property->services()->attach($service->id, $pivotData);
            $now = now();
            $billsGeneratedThisAttach = 0;

            // If any service is attached and is active, generate its initial bill(s).
            if ($pivotData['status'] === 'active') {
                $property->loadMissing('residents.pivot');
                $priceForBill = (float)$validated['price']; // Price from the attach request
                $billingTypeForCalc = $validated['billing_type']; // Billing type from the attach request

                $eligibleResidents = $this->getEligibleResidentsForService($property->residents, $service);

                foreach ($eligibleResidents as $resident) {
                    $this->_createBillForService(
                        $property,
                        $service,
                        $resident,
                        $priceForBill,
                        $billingTypeForCalc,
                        $now,
                        $request->user()->id,
                        "(Initial)"
                    );
                    $billsGeneratedThisAttach++;
                }

                if ($billsGeneratedThisAttach > 0) {
                    // Update last_billed_at for the newly attached service to mark this initial billing
                    $property->services()->updateExistingPivot($service->id, ['last_billed_at' => $now]);
                }
            }

            // Reload the property with the newly attached service, including potentially updated pivot data
            // Ensure all relevant pivot fields are loaded for the resource.
            $reloadedService = $property->services()
                ->withPivot('billing_type', 'price', 'status', 'details', 'activated_at', 'expires_at', 'last_billed_at', 'created_at', 'updated_at')
                ->find($service->id);

            return $this->createdResponse(
                'Service attached to property successfully' . ($billsGeneratedThisAttach > 0 ? " and {$billsGeneratedThisAttach} initial bill(s) generated." : '.'),
                new PropertyServiceResource($reloadedService)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update the relationship between a property and a service.
     */
    public function update(PropertyServiceRequest $request, int $propertyId, int $serviceId): JsonResponse
    {
        try {
            $validated = $request->validated();

            $property = Property::findOrFail($propertyId); // Ensure property exists

            // Fetch the specific service attached to this property, including its pivot data
            $serviceInstance = $property->services()
                ->withPivot('billing_type', 'price', 'status', 'details', 'activated_at', 'expires_at', 'last_billed_at')
                ->findOrFail($serviceId);

            if (!$serviceInstance) { // Should be caught by findOrFail, but as a safeguard
                return $this->notFoundResponse('Service is not attached to this property');
            }

            // Prepare the pivot data with only the fields that were provided in the request
            $pivotDataToUpdate = array_filter([
                'billing_type' => $validated['billing_type'] ?? null,
                'price' => $validated['price'] ?? null,
                'status' => $validated['status'] ?? null,
                'details' => $validated['details'] ?? null,
                'activated_at' => $validated['activated_at'] ?? null,
                'expires_at' => $validated['expires_at'] ?? null,
            ], function ($value) {
                return $value !== null;
            });

            // Handle immediate billing for pre-paid service renewals/updates
            if ($serviceInstance->pivot->billing_type === 'prepaid') {
                $property->loadMissing('residents.pivot'); // Ensure residents and their relationship types are loaded

                $priceForNewBill = $validated['price'] ?? $serviceInstance->pivot->price;
                $eligibleResidents = $this->getEligibleResidentsForService($property->residents, $serviceInstance);
                $now = now();
                $billsGeneratedThisUpdate = 0;

                foreach ($eligibleResidents as $resident) {
                    $this->_createBillForService(
                        $property,
                        $serviceInstance,
                        $resident,
                        (float)$priceForNewBill,
                        'prepaid', // Billing type for calculation is explicitly 'prepaid' here
                        $now,
                        $request->user()->id,
                        "(Pre-paid Renewal)"
                    );
                    $billsGeneratedThisUpdate++;
                }

                if ($billsGeneratedThisUpdate > 0) {
                    $pivotDataToUpdate['last_billed_at'] = $now; // Mark this new pre-paid period as billed
                }
            }

            // Update the pivot record with validated data and potentially new last_billed_at
            if (!empty($pivotDataToUpdate)) {
                $property->services()->updateExistingPivot($serviceId, $pivotDataToUpdate);
            }

            // Reload the property with the updated service details for the response
            $property->load(['services' => function ($query) use ($serviceId) {
                $query->where('services.id', $serviceId);
            }]);

            return $this->successResponse(
                'Property-service relationship updated successfully',
                new PropertyServiceResource($property->services->first())
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Detach a service from a property.
     */
    public function detach(int $propertyId, int $serviceId, Request $request): JsonResponse
    {
        try {
            // Only admins can detach services
            if ($request->user()->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can remove services from properties');
            }

            $property = Property::findOrFail($propertyId);

            // Check if the service is attached to the property
            $isAttached = $property->services()->where('services.id', $serviceId)->exists();

            if (!$isAttached) {
                return $this->notFoundResponse('Service is not attached to this property');
            }

            // Instead of detaching (hard delete), we'll update the pivot with deleted_at
            $property->services()->updateExistingPivot($serviceId, [
                'deleted_at' => now()
            ]);

            return $this->successResponse('Service detached from property successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get a specific property-service relationship.
     */
    public function show(int $propertyId, int $serviceId, Request $request): JsonResponse
    {
        try {
            $property = Property::findOrFail($propertyId);
            $user = $request->user();

            // Check access permission for residents
            if ($user->getTable() === 'residents') {
                $isResidentOfProperty = $property->residents()
                    ->where('resident_id', $user->id)
                    ->exists();

                if (!$isResidentOfProperty) {
                    return $this->forbiddenResponse('You do not have access to this property');
                }
            }

            // Get the service with pivot data
            $service = $property->services()
                ->where('services.id', $serviceId)
                ->first();

            if (!$service) {
                return $this->notFoundResponse('Service is not attached to this property');
            }

            return $this->successResponse(
                'Property-service relationship retrieved successfully',
                new PropertyServiceResource($service)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Activate a service for a property.
     */
    public function activate(int $propertyId, int $serviceId, Request $request): JsonResponse
    {
        try {
            // Only admins can activate services
            if ($request->user()->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can activate services for properties');
            }

            $property = Property::findOrFail($propertyId);

            // Check if the service is attached to the property
            $isAttached = $property->services()->where('services.id', $serviceId)->exists();

            if (!$isAttached) {
                return $this->notFoundResponse('Service is not attached to this property');
            }

            // Update the pivot record
            $property->services()->updateExistingPivot($serviceId, [
                'status' => 'active',
                'activated_at' => now(),
            ]);

            // Reload the property with the updated service
            $property->load(['services' => function ($query) use ($serviceId) {
                $query->where('services.id', $serviceId);
            }]);

            return $this->successResponse(
                'Service activated for property successfully',
                new PropertyServiceResource($property->services->first())
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Deactivate a service for a property.
     */
    public function deactivate(int $propertyId, int $serviceId, Request $request): JsonResponse
    {
        try {
            // Only admins can deactivate services
            if ($request->user()->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can deactivate services for properties');
            }

            $property = Property::findOrFail($propertyId);

            // Check if the service is attached to the property
            $isAttached = $property->services()->where('services.id', $serviceId)->exists();

            if (!$isAttached) {
                return $this->notFoundResponse('Service is not attached to this property');
            }

            // Update the pivot record
            $property->services()->updateExistingPivot($serviceId, [
                'status' => 'inactive',
            ]);

            // Reload the property with the updated service
            $property->load(['services' => function ($query) use ($serviceId) {
                $query->where('services.id', $serviceId);
            }]);

            return $this->successResponse(
                'Service deactivated for property successfully',
                new PropertyServiceResource($property->services->first())
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Generate bills for a property's services.
     */
    public function generateBills(int $propertyId, Request $request): JsonResponse
    {
        try {
            // Only admins can generate bills
            if ($request->user()->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can generate bills for property services');
            }

            $property = Property::with([
                'services' => function ($query) {
                    $query->wherePivot('status', 'active');
                },
                'residents' => function ($query) {
                    // Get residents with relationship to determine who pays what
                    $query->withPivot('relationship_type');
                }
            ])->findOrFail($propertyId);

            // If no active services or no residents, return error
            if ($property->services->isEmpty() || $property->residents->isEmpty()) {
                return $this->errorResponse('Property has no active services or no residents', 422);
            }

            $billsGenerated = 0;
            $now = now();

            // Create bills for each service for the appropriate residents
            foreach ($property->services as $service) {
                $pivot = $service->pivot; // Pivot data from property_service table

                $shouldBill = false;
                // Handle Pre-paid services (Electricity, Gas) - Bill ONCE then stop.
                if ($pivot->billing_type === 'prepaid') {
                    if ($pivot->last_billed_at === null) {
                        $shouldBill = true; // Bill if never billed before
                    }
                }
                // Handle Recurring services (Water, Security, Cleaning)
                elseif ($service->is_recurring) {
                    // Assumes these services have $service->is_recurring = true and a valid $service->recurrence
                    if (!$this->isTooBilledRecently($pivot->last_billed_at, $now, $service->recurrence)) {
                        $shouldBill = true; // Bill if it's due
                    }
                }
                // Note: Services that are not 'prepaid' and not 'is_recurring' will not set $shouldBill to true,
                // and thus will be skipped by the 'continue' below. This aligns with the specified requirements.

                if (!$shouldBill) {
                    continue; // Skip this service if it doesn't meet billing criteria
                }

                // Find residents who should be billed for this service type
                $eligibleResidents = $this->getEligibleResidentsForService($property->residents, $service);

                if ($eligibleResidents->isEmpty()) {
                    continue; // Skip if no eligible residents for this service
                }

                $billsGeneratedForThisService = 0;
                foreach ($eligibleResidents as $resident) {
                    $this->_createBillForService(
                        $property,
                        $service,
                        $resident,
                        (float)$pivot->price,
                        $pivot->billing_type,
                        $now,
                        $request->user()->id,
                        "" // No special note for standard scheduled bills
                    );
                    $billsGenerated++;
                    $billsGeneratedForThisService++;
                }

                // Update last_billed_at only if bills were actually generated for this service
                if ($billsGeneratedForThisService > 0) {
                    $property->services()->updateExistingPivot($service->id, [
                        'last_billed_at' => $now
                    ]);
                }
            }

            if ($billsGenerated === 0) {
                return $this->successResponse('No bills were generated. Services may have been billed recently.');
            }

            return $this->successResponse("Generated {$billsGenerated} bills for property services successfully");
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Check if a service was billed too recently.
     */
    private function isTooBilledRecently($lastBilledAt, \Illuminate\Support\Carbon $now, ?string $recurrence): bool
    {
        if (!$lastBilledAt) {
            return false; // Not billed before, so not "too recently"
        }

        $lastBilledCarbon = \Carbon\Carbon::parse($lastBilledAt); // Use FQCN for Carbon parse
        $nextBillingDate = $lastBilledCarbon->copy();

        switch (strtolower($recurrence ?? '')) { // Handle null recurrence and case-insensitivity
            case 'monthly':
                $nextBillingDate->addMonthNoOverflow();
                break;
            case 'quarterly':
                $nextBillingDate->addMonthsNoOverflow(3);
                break;
            case 'yearly':
                $nextBillingDate->addYearNoOverflow();
                break;
            default:
                // If recurrence is not specified or unrecognized for a service
                // that is being checked for recurring billing, this is an issue.
                // To prevent over-billing, consider it "too recently" by default.
                return true;
        }

        return $now->lt($nextBillingDate); // True if $now is Less Than $nextBillingDate (i.e., too early)
    }

    /**
     * Calculate the next billing date based on recurrence.
     */
    private function calculateNextBillingDate(string $recurrence, \Illuminate\Support\Carbon $baseDate)
    {
        $nextDate = $baseDate->copy();

        return match (strtolower($recurrence)) {
            'monthly' => $nextDate->addMonthNoOverflow(),
            'quarterly' => $nextDate->addMonthsNoOverflow(3),
            'yearly' => $nextDate->addYearNoOverflow(),
            default => $nextDate->addMonthNoOverflow(), // Default to monthly
        };
    }

    /**
     * Get eligible residents for a service.
     */
    private function getEligibleResidentsForService($residents, $service)
    {
        // Different service types might be billed to different residents
        // For example, owners might pay for some services, while renters pay for others
        $serviceType = $service->type;

        return $residents->filter(function ($resident) use ($serviceType) {
            $relationshipType = $resident->pivot->relationship_type;

            // Define which relationship types pay for which services
            $serviceResponsibilities = [
                'buyer' => ['security', 'cleaning', 'other'], // Owners pay for security, cleaning, etc.
                'co_buyer' => ['security', 'cleaning', 'other'],
                'renter' => ['electricity', 'gas', 'water'], // Renters pay for utilities
            ];

            // If relationship type isn't defined, default to primary
            if (!isset($serviceResponsibilities[$relationshipType])) {
                return true; // Primary pays for all
            }

            return in_array($serviceType, $serviceResponsibilities[$relationshipType]);
        });
    }

    /**
     * Calculate bill amount based on billing type.
     */
    private function calculateBillAmount(string $billingType, float $basePrice, Property $property, $resident)
    {
        return match ($billingType) {
            'fixed' => $basePrice, // Fixed price
            'area_based' => $basePrice * $property->area / 100, // Price per 100 sq meters
            'prepaid' => $basePrice, // Prepaid plans use the base price
            default => $basePrice,
        };
    }

    /**
     * Helper method to create and store a bill for a service.
     */
    private function _createBillForService(
        Property $property,
        Service $serviceModel,
        \App\Models\Resident $resident, // Explicitly type-hint Resident
        float $priceForBillCalculation,
        string $billingTypeForCalculation,
        \Illuminate\Support\Carbon $billingTimestamp,
        int $adminUserId,
        string $descriptionNote
    ): void {
        $billAmount = $this->calculateBillAmount($billingTypeForCalculation, $priceForBillCalculation, $property, $resident);

        $descriptionBase = "Service: {$serviceModel->name}";
        if (!empty(trim($descriptionNote))) {
            $descriptionBase = "Service {$descriptionNote}: {$serviceModel->name}";
        }

        $billData = [
            'property_id' => $property->id,
            'resident_id' => $resident->id,
            'bill_type' => strtolower($serviceModel->type), // e.g., 'water', 'electricity'
            'amount' => $billAmount,
            'currency' => $serviceModel->currency,
            'due_date' => $billingTimestamp->copy()->addDays(15),
            'description' => $descriptionBase,
            'status' => 'pending',
            'created_by' => $adminUserId,
        ];

        if ($serviceModel->is_recurring) {
            $billData['recurrence'] = $serviceModel->recurrence;
            // Pass the current billingTimestamp as the base for calculating the *next* billing date
            $billData['next_billing_date'] = $this->calculateNextBillingDate($serviceModel->recurrence, $billingTimestamp);
        }

        app(\App\Services\BillingService::class)->createBill($billData);
    }
}
