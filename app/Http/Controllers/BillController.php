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

    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();

            $query = Bill::query()->with(['property', 'resident']);

            // If user is not an admin, only show their bills
            if ($user->getTable() === 'residents') {
                $query->where('resident_id', $user->id);
            }

            $bills = $query->sort($request)
                ->paginate($request->get('per_page', 10));

            return BillResource::collection($bills);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function filter(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();

            $query = Bill::query()->with(['property', 'resident']);

            // If user is not an admin, only show their bills
            if ($user->getTable() === 'residents') {
                $query->where('resident_id', $user->id);
            }

            $bills = $query
                ->sort($request)
                ->filter($request)
                ->paginate($request->get('per_page', 10));

            return BillResource::collection($bills);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function show($id, Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $bill = Bill::with(['property', 'resident', 'payments'])->findOrFail($id);

            if ($user->getTable() === 'residents' && $bill->resident_id !== $user->id) {
                return $this->errorResponse('You are not authorized!');
            }

            return $this->successResponse(
                'Bill retrieved successfully',
                new BillResource($bill)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

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

    public function restore(int $id): JsonResponse
    {
        return $this->restoreModel(Bill::class, $id);
    }

    public function trashed(Request $request): JsonResponse
    {
        return $this->getTrashedModels(Bill::class, function ($query) use ($request) {
            if ($request->has('sort')) {
                $query->sort($request);
            }
        });
    }

    public function forceDelete(int $id): JsonResponse
    {
        return $this->forceDeleteModel(Bill::class, $id);
    }

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
