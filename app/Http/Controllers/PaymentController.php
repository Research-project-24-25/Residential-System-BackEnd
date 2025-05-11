<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Resident;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $payments = Payment::query()
                ->with(['bill', 'resident'])
                ->filter($request)
                ->sort($request)
                ->paginate($request->get('per_page', 10));

            return PaymentResource::collection($payments);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $payment = Payment::with(['bill', 'resident'])->findOrFail($id);

            return $this->successResponse(
                'Payment retrieved successfully',
                new PaymentResource($payment)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function store(PaymentRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Verify the bill exists
            $bill = Bill::findOrFail($validated['bill_id']);

            // If this is an admin processing a payment
            if ($request->user()->getTable() === 'admins') {
                $validated['processed_by'] = $request->user()->id;
            }

            // Process the payment
            $payment = $this->paymentService->processPayment($validated);

            return $this->createdResponse(
                'Payment processed successfully',
                new PaymentResource($payment)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function update($id, PaymentRequest $request): JsonResponse
    {
        try {
            $payment = Payment::findOrFail($id);
            $validated = $request->validated();

            // Only admins can update payments
            if ($request->user()->getTable() !== 'admins') {
                return $this->errorResponse('Unauthorized to update payments', 403);
            }

            // Update payment status
            $payment = $this->paymentService->updatePaymentStatus(
                $payment,
                $validated['status'],
                $request->user()->id
            );

            return $this->successResponse(
                'Payment updated successfully',
                new PaymentResource($payment)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function billPayments($billId, Request $request): ResourceCollection|JsonResponse
    {
        try {
            $bill = Bill::findOrFail($billId);

            $payments = $bill->payments()
                ->with(['resident'])
                ->filter($request)
                ->sort($request)
                ->paginate($request->get('per_page', 10));

            return PaymentResource::collection($payments);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function residentPayments($residentId, Request $request): ResourceCollection|JsonResponse
    {
        try {
            $resident = Resident::findOrFail($residentId);

            $payments = $resident->payments()
                ->with(['bill'])
                ->filter($request)
                ->sort($request)
                ->paginate($request->get('per_page', 10));

            return PaymentResource::collection($payments);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}