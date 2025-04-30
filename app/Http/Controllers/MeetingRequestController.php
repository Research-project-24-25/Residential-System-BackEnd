<?php

namespace App\Http\Controllers;

use App\Http\Requests\MeetingRequestStoreRequest;
use App\Http\Requests\MeetingRequestUpdateRequest;
use App\Http\Resources\MeetingRequestResource;
use App\Models\MeetingRequest;
use App\Models\Property;
use App\Models\Admin;
use App\Notifications\MeetingRequestStatusChanged;
use App\Notifications\NewMeetingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MeetingRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $query = MeetingRequest::query();

            if ($request->user()->getTable() === 'admins') {
                $requests = $query->with(['property', 'user', 'admin'])
                    ->sort($request)
                    ->paginate($perPage);
            } else {
                $requests = $query->where('user_id', $request->user()->id)
                    ->with('property')
                    ->sort($request)
                    ->paginate($perPage);
            }

            return MeetingRequestResource::collection($requests);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get filtered meeting requests
     * 
     * @param Request $request
     * @return ResourceCollection|JsonResponse
     */
    public function filter(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $query = MeetingRequest::query();

            if ($request->user()->getTable() === 'admins') {
                $requests = $query->with(['property', 'user', 'admin'])
                    ->filter($request)
                    ->sort($request)
                    ->paginate($perPage);
            } else {
                $requests = $query->where('user_id', $request->user()->id)
                    ->with('property')
                    ->filter($request)
                    ->sort($request)
                    ->paginate($perPage);
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

            $data = $request->validated();
            $data['user_id'] = $user->id;
            $data['status'] = 'pending';

            // Handle ID document upload
            if ($request->hasFile('id_document')) {
                $image = $request->file('id_document');
                $filename = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('meeting-documents'), $filename);
                $data['id_document'] = 'meeting-documents/' . $filename;
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

            // Check if user is authorized to view this meeting request
            if ($user->getTable() !== 'admins' && $meetingRequest->user_id !== $user->id) {
                return $this->forbiddenResponse('You are not authorized to view this meeting request');
            }

            // Load appropriate relationships based on user role
            if ($user->getTable() === 'admins') {
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

            $data = $request->validated();

            // Set admin who processed this request
            $data['admin_id'] = $admin->id;

            // Previous status to check if status has changed
            $previousStatus = $meetingRequest->status;

            // Set approved_date if status is being changed to approved
            if (isset($data['status']) && $data['status'] === 'approved' && $previousStatus !== 'approved') {
                $data['approved_date'] = $data['approved_date'] ?? now();
            }

            $meetingRequest->update($data);
            $meetingRequest->load(['property', 'user', 'admin']);

            // Notify user if status has changed
            if (isset($data['status']) && $previousStatus !== $data['status']) {
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

    /**
     * Delete a meeting request (Admin only).
     */
    public function destroy($id): JsonResponse
    {
        try {
            $meetingRequest = MeetingRequest::findOrFail($id);

            // Delete ID document if exists
            if ($meetingRequest->id_document) {
                Storage::disk('public')->delete($meetingRequest->id_document);
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
        $admins = Admin::all();

        // Notify each admin
        foreach ($admins as $admin) {
            $admin->notify(new NewMeetingRequest($meetingRequest));
        }
    }
}
