<?php

namespace App\Http\Controllers;

use App\Http\Requests\MeetingRequestRequest;
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

    public function filter(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $query = MeetingRequest::query()
                ->sort($request)
                ->filter($request);

            $user = $request->user();

            if ($user->getTable() === 'admins') {
                $requests = $query->with(['property', 'user', 'admin'])
                    ->paginate($perPage);
            } else {
                $requests = $query->where('user_id', $user->id)
                    ->with('property')
                    ->paginate($perPage);
            }

            return MeetingRequestResource::collection($requests);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function store(MeetingRequestRequest $request): JsonResponse
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

    public function update(MeetingRequestRequest $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $meetingRequest = MeetingRequest::findorFail($id);

            $data = $request->validated();

            // Handle admin updates
            if ($user->getTable() === 'admins') {
                // Set admin who processed this request
                $data['admin_id'] = $user->id;

                // Previous status to check if status has changed
                $previousStatus = $meetingRequest->status;

                // Set approved_date if status is being changed to approved
                if (isset($data['status']) && $data['status'] === 'approved' && $previousStatus !== 'approved') {
                    $data['approved_date'] = $data['approved_date'] ?? now();
                }
            }

            $meetingRequest->update($data);

            // Load relationships based on user type
            if ($user->getTable() === 'admins') {
                $meetingRequest->load(['property', 'user', 'admin']);
            } else {
                $meetingRequest->load('property');
            }

            // Notify user if status has changed (by admin)
            if (
                isset($data['status']) &&
                $user->getTable() === 'admins' &&
                $meetingRequest->getOriginal('status') !== $data['status']
            ) {
                // Make sure user and property are loaded
                if (!$meetingRequest->relationLoaded('user')) {
                    $meetingRequest->load('user');
                }

                if (!$meetingRequest->relationLoaded('property')) {
                    $meetingRequest->load('property');
                }

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

            // Notify admins about cancellation
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new MeetingRequestStatusChanged($meetingRequest));
            }

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

            // Delete ID document if it exists
            if ($meetingRequest->id_document) {
                $filePath = public_path($meetingRequest->id_document);

                // Ensure path is inside 'meeting-documents' for safety
                if (str_starts_with($meetingRequest->id_document, 'meeting-documents/') && file_exists($filePath)) {
                    @unlink($filePath); // Use @ to suppress warning if deletion fails
                }
            }

            $meetingRequest->delete();

            return $this->successResponse('Meeting request deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

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
