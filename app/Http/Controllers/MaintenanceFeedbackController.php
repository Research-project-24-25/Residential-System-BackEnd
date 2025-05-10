<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaintenanceFeedbackRequest;
use App\Http\Resources\MaintenanceFeedbackResource;
use App\Models\Admin;
use App\Models\MaintenanceFeedback;
use App\Models\MaintenanceRequest;
use App\Notifications\NewMaintenanceFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class MaintenanceFeedbackController extends Controller
{
    public function store(int $maintenanceRequestId, MaintenanceFeedbackRequest $request): JsonResponse
    {
        try {
            $maintenanceRequest = MaintenanceRequest::findOrFail($maintenanceRequestId);

            // Check if the maintenance request is completed
            if ($maintenanceRequest->status !== 'completed') {
                return $this->errorResponse(
                    'Feedback can only be provided for completed maintenance requests',
                    422
                );
            }

            // Check if feedback already exists
            if ($maintenanceRequest->feedback()->exists()) {
                return $this->errorResponse('Feedback has already been submitted for this maintenance request', 422);
            }

            // Create feedback
            $validated = $request->validated();
            $validated['maintenance_request_id'] = $maintenanceRequestId;
            $validated['resident_id'] = $request->user()->id;

            $feedback = MaintenanceFeedback::create($validated);

            // Update maintenance request to indicate it has feedback
            $maintenanceRequest->update(['has_feedback' => true]);

            // Load relationships
            $feedback->load(['maintenanceRequest', 'resident']);

            // Notify admins about the new feedback
            $this->notifyAdmins($feedback);

            return $this->createdResponse(
                'Feedback submitted successfully',
                new MaintenanceFeedbackResource($feedback)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function show(int $maintenanceRequestId, Request $request): JsonResponse
    {
        try {
            $maintenanceRequest = MaintenanceRequest::with('feedback')->findOrFail($maintenanceRequestId);

            // Check permissions
            if (
                $request->user()->getTable() !== 'admins' &&
                $maintenanceRequest->resident_id !== $request->user()->id
            ) {
                return $this->forbiddenResponse('You do not have permission to view this feedback');
            }

            // Check if feedback exists
            if (!$maintenanceRequest->feedback) {
                return $this->notFoundResponse('No feedback has been submitted for this maintenance request');
            }

            $feedback = $maintenanceRequest->feedback;
            $feedback->load(['maintenanceRequest', 'resident']);

            return $this->successResponse(
                'Feedback retrieved successfully',
                new MaintenanceFeedbackResource($feedback)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function update(int $maintenanceRequestId, MaintenanceFeedbackRequest $request): JsonResponse
    {
        try {
            $maintenanceRequest = MaintenanceRequest::with('feedback')->findOrFail($maintenanceRequestId);

            // Check if feedback exists
            if (!$maintenanceRequest->feedback) {
                return $this->notFoundResponse('No feedback has been submitted for this maintenance request');
            }

            $feedback = $maintenanceRequest->feedback;
            $validated = $request->validated();

            $feedback->update($validated);
            $feedback->load(['maintenanceRequest', 'resident']);

            return $this->successResponse(
                'Feedback updated successfully',
                new MaintenanceFeedbackResource($feedback)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function destroy(int $maintenanceRequestId, Request $request): JsonResponse
    {
        try {
            $maintenanceRequest = MaintenanceRequest::with('feedback')->findOrFail($maintenanceRequestId);

            // Check if feedback exists
            if (!$maintenanceRequest->feedback) {
                return $this->notFoundResponse('No feedback has been submitted for this maintenance request');
            }

            // Check permissions
            if (
                $request->user()->getTable() !== 'admins' &&
                $maintenanceRequest->resident_id !== $request->user()->id
            ) {
                return $this->forbiddenResponse('You do not have permission to delete this feedback');
            }

            // Delete feedback
            $maintenanceRequest->feedback->delete();

            // Update maintenance request to indicate it no longer has feedback
            $maintenanceRequest->update(['has_feedback' => false]);

            return $this->successResponse('Feedback deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            // Only admins can view all feedback
            if ($request->user()->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can view all feedback');
            }

            $filters = [];

            // Apply rating filter if provided
            if ($request->has('rating')) {
                $filters['rating'] = $request->input('rating');
            }

            // Apply satisfied filter if provided
            if ($request->has('satisfied')) {
                $filters['resolved_satisfactorily'] = filter_var($request->input('satisfied'), FILTER_VALIDATE_BOOLEAN);
            }

            $query = MaintenanceFeedback::with(['maintenanceRequest', 'resident']);

            // Apply filters
            foreach ($filters as $field => $value) {
                $query->where($field, $value);
            }

            // Sort and paginate
            $feedback = $query->sort($request)
                ->paginate($request->get('per_page', 10));

            return $this->successResponse(
                'Feedback retrieved successfully',
                MaintenanceFeedbackResource::collection($feedback)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function residentFeedback(int $residentId, Request $request): JsonResponse
    {
        try {
            // Check permissions
            if (
                $request->user()->getTable() !== 'admins' &&
                $residentId !== $request->user()->id
            ) {
                return $this->forbiddenResponse('You do not have permission to view this feedback');
            }

            $feedback = MaintenanceFeedback::where('resident_id', $residentId)
                ->with(['maintenanceRequest'])
                ->sort($request)
                ->paginate($request->get('per_page', 10));

            return $this->successResponse(
                'Resident feedback retrieved successfully',
                MaintenanceFeedbackResource::collection($feedback)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    private function notifyAdmins(MaintenanceFeedback $feedback): void
    {
        // Get all admins
        $admins = Admin::all();

        // Notify each admin
        foreach ($admins as $admin) {
            $admin->notify(new NewMaintenanceFeedback($feedback));
        }
    }
}
