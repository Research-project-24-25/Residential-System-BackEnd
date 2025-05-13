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
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();
            $query = Payment::query()->with(['bill', 'resident']);

            if ($user && $user->getTable() === 'residents') {
                $query->where('resident_id', $user->id);
            }

            $payments = $query
                ->sort($request)
                ->paginate($request->get('per_page', 10));

            return PaymentResource::collection($payments);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function filter(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();
            $query = Payment::query()->with(['bill', 'resident']);

            if ($user && $user->getTable() === 'residents') {
                $query->where('resident_id', $user->id);
            }

            $payments = $query->filter($request)
                ->sort($request)
                ->paginate($request->get('per_page', 10));

            return PaymentResource::collection($payments);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function show($id, Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $payment = Payment::with(['bill', 'resident'])->findOrFail($id);

            if ($user && $user->getTable() === 'residents' && $payment->resident_id !== $user->id) {
                return $this->errorResponse('You are not authorized to view this payment.', 403);
            }

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
            // Only admins can create payments
            if ($request->user()->getTable() !== 'admins') {
                return $this->errorResponse('Unauthorized to create payments. Payments are cash only and processed by admin.', 403);
            }

            $validated = $request->validated();

            // Verify the bill exists
            $bill = Bill::findOrFail($validated['bill_id']);

            $resident = Resident::findOrFail($validated['resident_id']);

            if ($bill->resident_id !== $resident->id) {
                return $this->errorResponse('The specified bill does not belong to this resident');
            }

            $validated['processed_by'] = $request->user()->id;

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
