<?php

namespace App\Http\Controllers;

use App\Http\Requests\BillRequest;
use App\Http\Resources\BillResource;
use App\Models\Bill;
use App\Models\Property;
use App\Models\Resident;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Throwable;

class BillController extends Controller
{
    public function __construct(private BillingService $billingService) {}

    /**
     * List all bills with filtering and pagination
     *
     * @param Request $request
     * @return ResourceCollection|JsonResponse
     */
    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $bills = Bill::query()
                ->with(['property', 'resident'])
                ->filter($request)
                ->sort($request)
                ->paginate($request->get('per_page', 10));

            return BillResource::collection($bills);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get a single bill
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $bill = Bill::with(['property', 'resident', 'payments'])->findOrFail($id);

            return $this->successResponse(
                'Bill retrieved successfully',
                new BillResource($bill)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Create a new bill
     *
     * @param BillRequest $request
     * @return JsonResponse
     */
    public function store(BillRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Verify property and resident exist and are related
            $property = Property::findOrFail($validated['property_id']);
            $resident = Resident::findOrFail($validated['resident_id']);

            // Check if resident is related to this property
            $isRelated = $property->residents()->where('resident_id', $resident->id)->exists();
            if (!$isRelated) {
                return $this->errorResponse('The resident is not related to this property', 422);
            }

            // Add the authenticated admin ID
            $validated['created_by'] = $request->user()->id;

            // Create the bill
            $bill = $this->billingService->createBill($validated);

            return $this->createdResponse(
                'Bill created successfully',
                new BillResource($bill)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update an existing bill
     *
     * @param int $id
     * @param BillRequest $request
     * @return JsonResponse
     */
    public function update($id, BillRequest $request): JsonResponse
    {
        try {
            $bill = Bill::findOrFail($id);
            $validated = $request->validated();

            // Check if bill can be updated (not fully paid)
            if ($bill->is_fully_paid) {
                return $this->errorResponse('Cannot update a fully paid bill', 422);
            }

            // Update the bill
            $bill = $this->billingService->updateBill($bill, $validated);

            return $this->successResponse(
                'Bill updated successfully',
                new BillResource($bill)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Delete a bill
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $bill = Bill::findOrFail($id);

            // Check if bill can be deleted (no payments made)
            if ($bill->payments()->exists()) {
                return $this->errorResponse('Cannot delete a bill with payments', 422);
            }

            $bill->delete();

            return $this->successResponse('Bill deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get bills for a specific property
     *
     * @param int $propertyId
     * @param Request $request
     * @return ResourceCollection|JsonResponse
     */
    public function propertyBills($propertyId, Request $request): ResourceCollection|JsonResponse
    {
        try {
            $property = Property::findOrFail($propertyId);

            $bills = $property->bills()
                ->with(['resident', 'payments'])
                ->filter($request)
                ->sort($request)
                ->paginate($request->get('per_page', 10));

            return BillResource::collection($bills);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get bills for a specific resident
     *
     * @param int $residentId
     * @param Request $request
     * @return ResourceCollection|JsonResponse
     */
    public function residentBills($residentId, Request $request): ResourceCollection|JsonResponse
    {
        try {
            $resident = Resident::findOrFail($residentId);

            $bills = $resident->bills()
                ->with(['property', 'payments'])
                ->filter($request)
                ->sort($request)
                ->paginate($request->get('per_page', 10));

            return BillResource::collection($bills);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Generate recurring bills 
     * This method is intended to be called by a scheduled task
     *
     * @return JsonResponse
     */
    public function generateRecurringBills(): JsonResponse
    {
        try {
            $count = $this->billingService->generateRecurringBills();

            return $this->successResponse("Generated $count recurring bills successfully");
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
