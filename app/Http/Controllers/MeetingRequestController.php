<?php

namespace App\Http\Controllers;

use App\Http\Requests\MeetingRequestRequest;
use App\Http\Resources\MeetingRequestResource;
use App\Models\MeetingRequest;
use App\Models\Property;
use App\Notifications\MeetingRequestStatusChanged;
use App\Notifications\NewMeetingRequest;
use App\Services\AdminNotificationService;
use App\Traits\HandlesFileUploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Throwable;

class MeetingRequestController extends Controller
{
    use HandlesFileUploads;

    protected $adminNotificationService;

    public function __construct(AdminNotificationService $adminNotificationService)
    {
        $this->adminNotificationService = $adminNotificationService;
    }

    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $query = MeetingRequest::query();

            if ($request->user()->getTable() === 'admins') {
                $requests = $query->with(['property', 'user', 'admin'])
                    ->sort($request)
                    ->search($request)
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
                ->search($request)
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
                $data['id_document'] = $this->handleMeetingDocument($request->file('id_document'));
            }

            $meetingRequest = MeetingRequest::create($data);

            // Load the related property
            $meetingRequest->load('property');

            // Notify an admin about new meeting request
            $this->adminNotificationService->notifyAdmin(new NewMeetingRequest($meetingRequest));

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

            // Notify the admin who processed the request if there is one
            if ($meetingRequest->admin_id) {
                $meetingRequest->admin->notify(new MeetingRequestStatusChanged($meetingRequest));
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
            $this->removeMeetingDocument($meetingRequest->id_document);

            $meetingRequest->delete();

            return $this->successResponse('Meeting request deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function restore(int $id): JsonResponse
    {
        return $this->restoreModel(MeetingRequest::class, $id);
    }

    public function trashed(Request $request): JsonResponse
    {
        return $this->getTrashedModels(MeetingRequest::class, function ($query) use ($request) {
            if ($request->has('sort')) {
                $query->sort($request);
            }
        });
    }

    public function forceDelete(int $id): JsonResponse
    {
        return $this->forceDeleteModel(MeetingRequest::class, $id);
    }
}
