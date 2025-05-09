<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaintenanceRequestRequest;
use App\Http\Resources\MaintenanceRequestResource;
use App\Models\Admin;
use App\Models\Bill;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Notifications\MaintenanceRequestStatusChanged;
use App\Notifications\NewMaintenanceRequest;
use Throwable;

class MaintenanceRequestController extends Controller
{
    private BillingService $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    /**
     * Display a listing of the maintenance requests.
     *
     * @param Request $request
     * @return ResourceCollection|JsonResponse
     */
    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $query = MaintenanceRequest::query();

            // If user is a resident, only show their requests
            if ($request->user()->getTable() === 'residents') {
                $query->where('resident_id', $request->user()->id);
            }

            $maintenanceRequests = $query->with(['maintenance', 'property', 'resident', 'admin', 'feedback'])
                ->sort($request)
                ->paginate($perPage);

            return MaintenanceRequestResource::collection($maintenanceRequests);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get filtered maintenance requests
     * 
     * @param MaintenanceRequestRequest $request
     * @return ResourceCollection|JsonResponse
     */
    public function filter(MaintenanceRequestRequest $request): ResourceCollection|JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $query = MaintenanceRequest::query();

            // If user is a resident, only show their requests
            if ($request->user()->getTable() === 'residents') {
                $query->where('resident_id', $request->user()->id);
            }

            $maintenanceRequests = $query->with(['maintenance', 'property', 'resident', 'admin', 'feedback'])
                ->filter($request)
                ->sort($request)
                ->paginate($perPage);

            return MaintenanceRequestResource::collection($maintenanceRequests);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Store a newly created maintenance request in storage.
     *
     * @param MaintenanceRequestRequest $request
     * @return JsonResponse
     */
    public function store(MaintenanceRequestRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // If a resident is creating this, set resident_id
            if ($request->user()->getTable() === 'residents') {
                $validated['resident_id'] = $request->user()->id;
            }

            // Verify the property exists
            $property = Property::findOrFail($validated['property_id']);

            // Verify this resident belongs to this property if it's a resident
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

            // Handle image uploads
            if ($request->hasFile('images')) {
                $images = $this->handleImageUploads($request->file('images'));
                $validated['images'] = $images;
            } else {
                $validated['images'] = [];
            }

            // Create the maintenance request
            $maintenanceRequest = MaintenanceRequest::create($validated);
            $maintenanceRequest->load(['maintenance', 'property', 'resident']);

            // Notify admins about new request
            $this->notifyAdmins($maintenanceRequest);

            return $this->createdResponse(
                'Maintenance request created successfully',
                new MaintenanceRequestResource($maintenanceRequest)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Display the specified maintenance request.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $maintenanceRequest = MaintenanceRequest::with(['maintenance', 'property', 'resident', 'admin', 'bill', 'feedback'])
                ->findOrFail($id);

            // If a resident is viewing, verify ownership
            if ($request->user()->getTable() === 'residents' && $maintenanceRequest->resident_id !== $request->user()->id) {
                return $this->forbiddenResponse('You do not have permission to view this maintenance request');
            }

            return $this->successResponse(
                'Maintenance request retrieved successfully',
                new MaintenanceRequestResource($maintenanceRequest)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update the specified maintenance request in storage.
     *
     * @param MaintenanceRequestRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(MaintenanceRequestRequest $request, int $id): JsonResponse
    {
        try {
            $maintenanceRequest = MaintenanceRequest::findOrFail($id);
            $validated = $request->validated();

            // If a resident is updating, verify ownership
            if ($request->user()->getTable() === 'residents') {
                if ($maintenanceRequest->resident_id !== $request->user()->id) {
                    return $this->forbiddenResponse('You do not have permission to update this maintenance request');
                }

                // Residents can only update description and issue details and only if the request is pending
                if ($maintenanceRequest->status !== 'pending') {
                    return $this->errorResponse('You can only update pending maintenance requests', 422);
                }

                $updateData = array_intersect_key($validated, array_flip(['description', 'issue_details']));

                // Handle image uploads
                if ($request->hasFile('images')) {
                    $currentImages = is_array($maintenanceRequest->getRawOriginal('images'))
                        ? $maintenanceRequest->getRawOriginal('images')
                        : json_decode($maintenanceRequest->getRawOriginal('images') ?? '[]', true);

                    $newImages = $this->handleImageUploads($request->file('images'));
                    $updateData['images'] = array_merge($currentImages ?? [], $newImages);
                }

                $maintenanceRequest->update($updateData);
            }
            // Admin updates
            else if ($request->user()->getTable() === 'admins') {
                // Set admin ID if status is changing
                if (isset($validated['status']) && $validated['status'] !== $maintenanceRequest->status) {
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
                    (!$maintenanceRequest->bill_id || (isset($validated['status']) && $validated['status'] === 'completed'))
                ) {
                    // Create a bill for this maintenance
                    $billData = [
                        'property_id' => $maintenanceRequest->property_id,
                        'resident_id' => $maintenanceRequest->resident_id,
                        'bill_type' => 'maintenance',
                        'amount' => $validated['final_cost'],
                        'due_date' => now()->addDays(15), // Due in 15 days
                        'description' => "Maintenance: " . ($maintenanceRequest->maintenance ?
                            $maintenanceRequest->maintenance->name . " - " : '') .
                            $maintenanceRequest->description,
                        'status' => 'pending',
                        'created_by' => $request->user()->id
                    ];

                    $bill = $this->billingService->createBill($billData);
                    $validated['bill_id'] = $bill->id;
                }

                $maintenanceRequest->update($validated);

                // Notify the resident about the status change if it changed
                if (isset($validated['status']) && $validated['status'] !== $maintenanceRequest->getOriginal('status')) {
                    // Make sure dependencies are loaded
                    if (!$maintenanceRequest->relationLoaded('property')) {
                        $maintenanceRequest->load('property');
                    }

                    // Reload resident relationship
                    $maintenanceRequest->load('resident');

                    // Send notification
                    $maintenanceRequest->resident->notify(new MaintenanceRequestStatusChanged($maintenanceRequest));
                }
            }

            $maintenanceRequest->load(['maintenance', 'property', 'resident', 'admin', 'bill', 'feedback']);

            return $this->successResponse(
                'Maintenance request updated successfully',
                new MaintenanceRequestResource($maintenanceRequest)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Cancel a maintenance request (resident only)
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function cancel(int $id, Request $request): JsonResponse
    {
        try {
            $maintenanceRequest = MaintenanceRequest::findOrFail($id);

            // Verify ownership
            if ($maintenanceRequest->resident_id !== $request->user()->id) {
                return $this->forbiddenResponse('You do not have permission to cancel this maintenance request');
            }

            // Can only cancel if pending or approved (not yet in progress)
            if (!in_array($maintenanceRequest->status, ['pending', 'approved'])) {
                return $this->errorResponse('Cannot cancel a maintenance request that is already in progress or completed', 422);
            }

            $maintenanceRequest->update([
                'status' => 'cancelled'
            ]);

            $maintenanceRequest->load(['maintenance', 'property', 'resident']);

            return $this->successResponse(
                'Maintenance request cancelled successfully',
                new MaintenanceRequestResource($maintenanceRequest)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Remove the specified maintenance request from storage (admin only)
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        try {
            // Only admins can delete maintenance requests
            if ($request->user()->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can delete maintenance requests');
            }

            $maintenanceRequest = MaintenanceRequest::findOrFail($id);

            // Cannot delete if there's an associated bill
            if ($maintenanceRequest->bill_id) {
                return $this->errorResponse('Cannot delete a maintenance request with an associated bill', 422);
            }

            // Remove images if they exist
            if (!empty($maintenanceRequest->getRawOriginal('images'))) {
                $images = json_decode($maintenanceRequest->getRawOriginal('images'), true) ?? [];
                $this->removeImages($images);
            }

            $maintenanceRequest->delete();

            return $this->successResponse('Maintenance request deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get maintenance requests for a specific property
     *
     * @param int $propertyId
     * @param Request $request
     * @return ResourceCollection|JsonResponse
     */
    public function propertyMaintenanceRequests(int $propertyId, Request $request): ResourceCollection|JsonResponse
    {
        try {
            $property = Property::findOrFail($propertyId);
            $query = MaintenanceRequest::where('property_id', $propertyId);

            // If resident, verify they belong to this property
            if ($request->user()->getTable() === 'residents') {
                $isRelated = $property->residents()
                    ->where('resident_id', $request->user()->id)
                    ->exists();

                if (!$isRelated) {
                    return $this->forbiddenResponse('You do not have permission to view these maintenance requests');
                }

                // Restrict to only their requests for this property
                $query->where('resident_id', $request->user()->id);
            }

            $maintenanceRequests = $query->with(['maintenance', 'resident', 'admin', 'feedback'])
                ->sort($request)
                ->paginate($request->get('per_page', 10));

            return MaintenanceRequestResource::collection($maintenanceRequests);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get maintenance requests for a specific resident
     *
     * @param int $residentId
     * @param Request $request
     * @return ResourceCollection|JsonResponse
     */
    public function residentMaintenanceRequests(int $residentId, Request $request): ResourceCollection|JsonResponse
    {
        try {
            // Verify permission
            if ($request->user()->getTable() === 'residents' && $request->user()->id !== $residentId) {
                return $this->forbiddenResponse('You do not have permission to view these maintenance requests');
            }

            $maintenanceRequests = MaintenanceRequest::where('resident_id', $residentId)
                ->with(['maintenance', 'property', 'admin', 'feedback'])
                ->sort($request)
                ->paginate($request->get('per_page', 10));

            return MaintenanceRequestResource::collection($maintenanceRequests);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get emergency maintenance requests (admin only)
     *
     * @param Request $request
     * @return ResourceCollection|JsonResponse
     */
    public function emergencyRequests(Request $request): ResourceCollection|JsonResponse
    {
        try {
            // Only admins can access this endpoint
            if ($request->user()->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can access emergency maintenance requests');
            }

            $maintenanceRequests = MaintenanceRequest::where('priority', 'emergency')
                ->whereIn('status', ['pending', 'approved', 'scheduled', 'in_progress'])
                ->with(['maintenance', 'property', 'resident', 'admin'])
                ->sort($request)
                ->paginate($request->get('per_page', 10));

            return MaintenanceRequestResource::collection($maintenanceRequests);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Handle uploading maintenance request images
     * 
     * @param array $images
     * @return array
     */
    private function handleImageUploads($images): array
    {
        $uploadedImages = [];

        foreach ($images as $image) {
            $filename = time() . '_' . $image->getClientOriginalName();
            // Store in public directory
            $image->move(public_path('maintenance-images'), $filename);
            $uploadedImages[] = 'maintenance-images/' . $filename;
        }

        return $uploadedImages;
    }

    /**
     * Remove maintenance request images
     * 
     * @param array $images
     * @return void
     */
    private function removeImages($images): void
    {
        if (!is_array($images)) {
            return;
        }

        foreach ($images as $image) {
            $path = public_path($image);
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Notify admins about new maintenance request
     * 
     * @param MaintenanceRequest $maintenanceRequest
     * @return void
     */
    private function notifyAdmins(MaintenanceRequest $maintenanceRequest): void
    {
        // Get all admins
        $admins = Admin::all();

        // Notify each admin
        foreach ($admins as $admin) {
            $admin->notify(new NewMaintenanceRequest($maintenanceRequest));
        }
    }
}
