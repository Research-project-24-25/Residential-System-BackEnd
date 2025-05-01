<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceRequestRequest;
use App\Http\Resources\ServiceRequestResource;
use App\Models\Bill;
use App\Models\Property;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Notifications\ServiceRequestStatusChanged;
use Throwable;

class ServiceRequestController extends Controller
{
    private BillingService $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    /**
     * Display a listing of the service requests.
     *
     * @param Request $request
     * @return ResourceCollection|JsonResponse
     */
    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $query = ServiceRequest::query();

            // If user is a resident, only show their requests
            if ($request->user()->getTable() === 'residents') {
                $query->where('resident_id', $request->user()->id);
            }

            $serviceRequests = $query->with(['service', 'property', 'resident', 'admin'])
                ->sort($request)
                ->paginate($perPage);

            return ServiceRequestResource::collection($serviceRequests);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get filtered service requests
     * 
     * @param ServiceRequestRequest $request
     * @return ResourceCollection|JsonResponse
     */
    public function filter(ServiceRequestRequest $request): ResourceCollection|JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $query = ServiceRequest::query();

            // If user is a resident, only show their requests
            if ($request->user()->getTable() === 'residents') {
                $query->where('resident_id', $request->user()->id);
            }

            $serviceRequests = $query->with(['service', 'property', 'resident', 'admin'])
                ->filter($request)
                ->sort($request)
                ->paginate($perPage);

            return ServiceRequestResource::collection($serviceRequests);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Store a newly created service request in storage.
     *
     * @param ServiceRequestRequest $request
     * @return JsonResponse
     */
    public function store(ServiceRequestRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // If a resident is creating this, set resident_id
            if ($request->user()->getTable() === 'residents') {
                $validated['resident_id'] = $request->user()->id;
            }

            // Verify the service and property exist
            $service = Service::findOrFail($validated['service_id']);
            $property = Property::findOrFail($validated['property_id']);

            // Verify this resident belongs to this property
            if ($request->user()->getTable() === 'residents') {
                $isRelated = $property->residents()
                    ->where('resident_id', $request->user()->id)
                    ->exists();

                if (!$isRelated) {
                    return $this->errorResponse('You are not associated with this property', 403);
                }
            }

            // Set initial status to pending
            $validated['status'] = 'pending';

            // Create the service request
            $serviceRequest = ServiceRequest::create($validated);
            $serviceRequest->load(['service', 'property', 'resident']);

            return $this->createdResponse(
                'Service request created successfully',
                new ServiceRequestResource($serviceRequest)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Display the specified service request.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $serviceRequest = ServiceRequest::with(['service', 'property', 'resident', 'admin', 'bill'])
                ->findOrFail($id);

            // If a resident is viewing, verify ownership
            if ($request->user()->getTable() === 'residents' && $serviceRequest->resident_id !== $request->user()->id) {
                return $this->forbiddenResponse('You do not have permission to view this service request');
            }

            return $this->successResponse(
                'Service request retrieved successfully',
                new ServiceRequestResource($serviceRequest)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update the specified service request in storage.
     *
     * @param ServiceRequestRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(ServiceRequestRequest $request, int $id): JsonResponse
    {
        try {
            $serviceRequest = ServiceRequest::findOrFail($id);
            $validated = $request->validated();

            // If a resident is updating, verify ownership
            if ($request->user()->getTable() === 'residents') {
                if ($serviceRequest->resident_id !== $request->user()->id) {
                    return $this->forbiddenResponse('You do not have permission to update this service request');
                }

                // Residents can only update description and only if the request is pending
                if ($serviceRequest->status !== 'pending') {
                    return $this->errorResponse('You can only update pending service requests', 422);
                }

                // Only allow updating description
                $serviceRequest->update(['description' => $validated['description']]);
            }
            // Admin updates
            else if ($request->user()->getTable() === 'admins') {
                // Set admin ID if status is changing
                if (isset($validated['status']) && $validated['status'] !== $serviceRequest->status) {
                    $validated['admin_id'] = $request->user()->id;
                }

                // If transitioning to completed, ensure completion_date is set
                if (isset($validated['status']) && $validated['status'] === 'completed' && !isset($validated['completion_date'])) {
                    $validated['completion_date'] = now();
                }

                // If admin is setting a final cost, create a bill if needed
                if (
                    isset($validated['final_cost']) &&
                    $validated['final_cost'] > 0 &&
                    (!$serviceRequest->bill_id || (isset($validated['status']) && $validated['status'] === 'completed'))
                ) {

                    // Create a bill for this service
                    $billData = [
                        'property_id' => $serviceRequest->property_id,
                        'resident_id' => $serviceRequest->resident_id,
                        'bill_type' => 'maintenance', // Or could be based on service type
                        'amount' => $validated['final_cost'],
                        'due_date' => now()->addDays(15), // Due in 15 days
                        'description' => "Service: {$serviceRequest->service->name} - {$serviceRequest->description}",
                        'status' => 'pending',
                        'created_by' => $request->user()->id
                    ];

                    $bill = $this->billingService->createBill($billData);
                    $validated['bill_id'] = $bill->id;
                }

                $serviceRequest->update($validated);

                // Notify the resident about the status change if it changed
                if (isset($validated['status']) && $validated['status'] !== $serviceRequest->getOriginal('status')) {
                    // Make sure service and property are loaded
                    if (!$serviceRequest->relationLoaded('service')) {
                        $serviceRequest->load('service');
                    }

                    if (!$serviceRequest->relationLoaded('property')) {
                        $serviceRequest->load('property');
                    }

                    // Reload resident relationship
                    $serviceRequest->load('resident');

                    // Send notification
                    $serviceRequest->resident->notify(new ServiceRequestStatusChanged($serviceRequest));
                }
            }

            $serviceRequest->load(['service', 'property', 'resident', 'admin', 'bill']);

            return $this->successResponse(
                'Service request updated successfully',
                new ServiceRequestResource($serviceRequest)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Cancel a service request (resident only)
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function cancel(int $id, Request $request): JsonResponse
    {
        try {
            $serviceRequest = ServiceRequest::findOrFail($id);

            // Verify ownership
            if ($serviceRequest->resident_id !== $request->user()->id) {
                return $this->forbiddenResponse('You do not have permission to cancel this service request');
            }

            // Can only cancel if pending or approved (not yet in progress)
            if (!in_array($serviceRequest->status, ['pending', 'approved'])) {
                return $this->errorResponse('Cannot cancel a service request that is already in progress or completed', 422);
            }

            $serviceRequest->update([
                'status' => 'cancelled'
            ]);

            $serviceRequest->load(['service', 'property', 'resident']);

            return $this->successResponse(
                'Service request cancelled successfully',
                new ServiceRequestResource($serviceRequest)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Remove the specified service request from storage (admin only)
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        try {
            // Only admins can delete service requests
            if ($request->user()->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can delete service requests');
            }

            $serviceRequest = ServiceRequest::findOrFail($id);

            // Cannot delete if there's an associated bill
            if ($serviceRequest->bill_id) {
                return $this->errorResponse('Cannot delete a service request with an associated bill', 422);
            }

            $serviceRequest->delete();

            return $this->successResponse('Service request deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get service requests for a specific property
     *
     * @param int $propertyId
     * @param Request $request
     * @return ResourceCollection|JsonResponse
     */
    public function propertyServiceRequests(int $propertyId, Request $request): ResourceCollection|JsonResponse
    {
        try {
            $property = Property::findOrFail($propertyId);
            $query = ServiceRequest::where('property_id', $propertyId);

            // If resident, verify they belong to this property
            if ($request->user()->getTable() === 'residents') {
                $isRelated = $property->residents()
                    ->where('resident_id', $request->user()->id)
                    ->exists();

                if (!$isRelated) {
                    return $this->forbiddenResponse('You do not have permission to view these service requests');
                }

                // Restrict to only their requests for this property
                $query->where('resident_id', $request->user()->id);
            }

            $serviceRequests = $query->with(['service', 'resident', 'admin'])
                ->sort($request)
                ->paginate($request->get('per_page', 10));

            return ServiceRequestResource::collection($serviceRequests);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get service requests for a specific resident
     *
     * @param int $residentId
     * @param Request $request
     * @return ResourceCollection|JsonResponse
     */
    public function residentServiceRequests(int $residentId, Request $request): ResourceCollection|JsonResponse
    {
        try {
            // Verify permission
            if ($request->user()->getTable() === 'residents' && $request->user()->id !== $residentId) {
                return $this->forbiddenResponse('You do not have permission to view these service requests');
            }

            $serviceRequests = ServiceRequest::where('resident_id', $residentId)
                ->with(['service', 'property', 'admin'])
                ->sort($request)
                ->paginate($request->get('per_page', 10));

            return ServiceRequestResource::collection($serviceRequests);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
