<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentMethodRequest;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use App\Models\Resident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Throwable;

class PaymentMethodController extends Controller
{
    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            // Only admins can see all payment methods
            if ($request->user()->getTable() !== 'admins') {
                return $this->errorResponse('Unauthorized', 403);
            }

            $paymentMethods = PaymentMethod::query()
                ->with(['resident'])
                ->filter($request)
                ->sort($request)
                ->paginate($request->get('per_page', 10));

            return PaymentMethodResource::collection($paymentMethods);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $paymentMethod = PaymentMethod::with(['resident'])->findOrFail($id);

            // Check authorization - only admins or the owner can see a payment method
            if (
                $request->user()->getTable() !== 'admins' &&
                ($request->user()->getTable() !== 'residents' || $request->user()->id !== $paymentMethod->resident_id)
            ) {
                return $this->errorResponse('Unauthorized', 403);
            }

            return $this->successResponse(
                'Payment method retrieved successfully',
                new PaymentMethodResource($paymentMethod)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function store(PaymentMethodRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // If resident creating their own payment method
            if ($request->user()->getTable() === 'residents') {
                $validated['resident_id'] = $request->user()->id;
            }
            // If admin creating for a resident, verify the resident exists
            else {
                $resident = Resident::findOrFail($validated['resident_id']);
            }

            // Check if this is set as default and handle accordingly
            if (!empty($validated['is_default']) && $validated['is_default']) {
                // Remove default from other payment methods for this resident
                PaymentMethod::where('resident_id', $validated['resident_id'])
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $paymentMethod = PaymentMethod::create($validated);

            return $this->createdResponse(
                'Payment method created successfully',
                new PaymentMethodResource($paymentMethod)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function update(int $id, PaymentMethodRequest $request): JsonResponse
    {
        try {
            $paymentMethod = PaymentMethod::findOrFail($id);
            $validated = $request->validated();

            // Check authorization
            if (
                $request->user()->getTable() !== 'admins' &&
                ($request->user()->getTable() !== 'residents' || $request->user()->id !== $paymentMethod->resident_id)
            ) {
                return $this->errorResponse('Unauthorized', 403);
            }

            // Handle setting as default
            if (!empty($validated['is_default']) && $validated['is_default'] && !$paymentMethod->is_default) {
                PaymentMethod::where('resident_id', $paymentMethod->resident_id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $paymentMethod->update($validated);

            return $this->successResponse(
                'Payment method updated successfully',
                new PaymentMethodResource($paymentMethod)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        try {
            $paymentMethod = PaymentMethod::findOrFail($id);

            // Check authorization
            if (
                $request->user()->getTable() !== 'admins' &&
                ($request->user()->getTable() !== 'residents' || $request->user()->id !== $paymentMethod->resident_id)
            ) {
                return $this->errorResponse('Unauthorized', 403);
            }

            // Check if payment method has been used in payments
            if ($paymentMethod->payments()->exists()) {
                return $this->errorResponse('Cannot delete a payment method that has been used in payments', 422);
            }

            $paymentMethod->delete();

            return $this->successResponse('Payment method deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function residentPaymentMethods(int $residentId, Request $request): ResourceCollection|JsonResponse
    {
        try {
            // Check authorization
            if (
                $request->user()->getTable() !== 'admins' &&
                ($request->user()->getTable() !== 'residents' || $request->user()->id !== $residentId)
            ) {
                return $this->errorResponse('Unauthorized', 403);
            }

            $resident = Resident::findOrFail($residentId);

            $paymentMethods = $resident->paymentMethods()
                ->filter($request)
                ->sort($request)
                ->paginate($request->get('per_page', 10));

            return PaymentMethodResource::collection($paymentMethods);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function setDefault(int $id, Request $request): JsonResponse
    {
        try {
            $paymentMethod = PaymentMethod::findOrFail($id);

            // Check authorization
            if (
                $request->user()->getTable() !== 'admins' &&
                ($request->user()->getTable() !== 'residents' || $request->user()->id !== $paymentMethod->resident_id)
            ) {
                return $this->errorResponse('Unauthorized', 403);
            }

            // Remove default from other payment methods
            PaymentMethod::where('resident_id', $paymentMethod->resident_id)
                ->where('is_default', true)
                ->update(['is_default' => false]);

            // Set this one as default
            $paymentMethod->update(['is_default' => true]);

            return $this->successResponse(
                'Payment method set as default successfully',
                new PaymentMethodResource($paymentMethod)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
