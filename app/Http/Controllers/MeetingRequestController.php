<?php

namespace App\Http\Controllers;

use App\Http\Requests\MeetingRequestStoreRequest;
use App\Http\Requests\MeetingRequestUpdateRequest;
use App\Http\Resources\MeetingRequestResource;
use App\Models\MeetingRequest;
use App\Models\Property;
use App\Notifications\MeetingRequestStatusChanged;
use App\Notifications\NewMeetingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Throwable;

class MeetingRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();
            $query = MeetingRequest::query();

            // If admin, show all requests
            if ($user->tokenCan('admin') || $user->tokenCan('super_admin')) {
                $requests = $query->with(['property', 'user', 'admin'])
                    ->filter($request)
                    ->sort($request)
                    ->paginate($request->get('per_page', 10));
            } else {
                // Regular user only sees their own requests
                $requests = $query->where('user_id', $user->id)
                    ->with('property')
                    ->filter($request)
                    ->sort($request)
                    ->paginate($request->get('per_page', 10));
            }

            return MeetingRequestResource::collection($requests);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MeetingRequestStoreRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $property = Property::findorFail($request->property_id);

            if (!$property) {
                return $this->notFoundResponse('Property not found');
            }

            $data = $request->validated();
            $data['user_id'] = $user->id;

            // Handle ID document upload
            if ($request->hasFile('id_document')) {
                $path = $request->file('id_document')->store('id_documents', 'public');
                $data['id_document'] = $path;
            }

            $meetingRequest = MeetingRequest::create($data);

            // Load the related property
            $meetingRequest->load('property');

            // Notify admins about new meeting request
            $this->notifyAdmins($meetingRequest);

            return $this->createdResponse(
                'Meeting request created successfully',
                new MeetingRequestResource($meetingRequest)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $meetingRequest = MeetingRequest::findorFail($id);

            if (!$meetingRequest) {
                return $this->notFoundResponse('Meeting request not found');
            }

            // Check if user is authorized to view this meeting request
            if (!$user->tokenCan('admin') && !$user->tokenCan('super_admin') && $meetingRequest->user_id !== $user->id) {
                return $this->forbiddenResponse('You are not authorized to view this meeting request');
            }

            // Load appropriate relationships based on user role
            if ($user->tokenCan('admin') || $user->tokenCan('super_admin')) {
                $meetingRequest->load(['property', 'user', 'admin']);
            } else {
                $meetingRequest->load('property');
            }

            return $this->successResponse(
                'Meeting request retrieved successfully',
                new MeetingRequestResource($meetingRequest)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update a meeting request (Admin only).
     */
    public function update(MeetingRequestUpdateRequest $request, $id): JsonResponse
    {
        try {
            $admin = $request->user();
            $meetingRequest = MeetingRequest::findorFail($id);

            if (!$meetingRequest) {
                return $this->notFoundResponse('Meeting request not found');
            }

            $data = $request->validated();

            // Set admin who processed this request
            $data['admin_id'] = $admin->id;

            // Previous status to check if status has changed
            $previousStatus = $meetingRequest->status;

            if ($data['status'] === 'approved') {
                $data['approved_date'] = now();
            }

            $meetingRequest->update($data);
            $meetingRequest->load(['property', 'user', 'admin']);

            // Notify user if status has changed
            if ($previousStatus !== $meetingRequest->status) {
                $meetingRequest->user->notify(new MeetingRequestStatusChanged($meetingRequest));
            }

            return $this->successResponse(
                'Meeting request updated successfully',
                new MeetingRequestResource($meetingRequest)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Cancel a meeting request (User can cancel their own requests).
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $meetingRequest = MeetingRequest::findorFail($id);

            if (!$meetingRequest) {
                return $this->notFoundResponse('Meeting request not found');
            }

            // Check if user is authorized to cancel this meeting request
            if ($meetingRequest->user_id !== $user->id) {
                return $this->forbiddenResponse('You are not authorized to cancel this meeting request');
            }

            // Check if meeting request can be cancelled
            if (!$meetingRequest->isPending() && !$meetingRequest->isApproved()) {
                return $this->errorResponse('This meeting request cannot be cancelled');
            }

            $meetingRequest->update([
                'status' => 'cancelled'
            ]);

            $meetingRequest->load('property');

            return $this->successResponse(
                'Meeting request cancelled successfully',
                new MeetingRequestResource($meetingRequest)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $meetingRequest = MeetingRequest::findOrFail($id);

            if (!$meetingRequest) {
                return $this->notFoundResponse('Meeting request not found');
            }

            $meetingRequest->delete();

            return $this->successResponse('Meeting request deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get upcoming meetings for the authenticated user.
     */
    public function upcoming(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();
            $query = MeetingRequest::query();

            // Only approved meetings with dates in the future
            $upcomingMeetings = $query->where('user_id', $user->id)
                ->where('status', 'approved')
                ->where('approved_date', '>=', now())
                ->with('property')
                ->orderBy('approved_date')
                ->paginate($request->get('per_page', 10));

            return MeetingRequestResource::collection($upcomingMeetings);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Notify admins about new meeting request.
     */
    private function notifyAdmins(MeetingRequest $meetingRequest): void
    {
        // Get all admins
        $admins = \App\Models\Admin::all();

        // Notify each admin
        foreach ($admins as $admin) {
            $admin->notify(new NewMeetingRequest($meetingRequest));
        }
    }
}
