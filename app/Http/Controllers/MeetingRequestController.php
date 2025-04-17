<?php

namespace App\Http\Controllers;

use App\Models\MeetingRequest;
use App\Models\User;
use App\Notifications\MeetingRequestCreated;
use App\Notifications\MeetingRequestUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MeetingRequestController extends Controller
{
    /**
     * Display a listing of meeting requests for the authenticated user.
     */
    public function index(Request $request)
    {
        try {
            $query = MeetingRequest::query();

            // If not admin, only show this user's meeting requests
            if ($request->user()->role !== 'admin') {
                $query->where('user_email', $request->user()->email);
            }

            // Apply search, sorting and pagination
            $query = $this->applySearch($query, $request, ['property_type', 'user_name', 'user_email', 'status']);
            $query = $this->applySorting($query, $request);
            $meetingRequests = $this->applyPagination($query, $request);

            return $this->successResponse('Meeting requests retrieved successfully', $meetingRequests);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Store a newly created meeting request in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'property_type' => ['required', 'string', 'in:apartment,house'],
                'property_id' => ['required', 'integer', 'exists:' . $request->property_type . 's,id'],
                'preferred_time' => ['required', 'date', 'after:now'],
                'message' => ['nullable', 'string', 'max:500'],
                'user_phone' => ['nullable', 'string', 'max:20'],
            ]);

            // Get authenticated user
            $user = Auth::user();

            // Check if user has already created a meeting request for this property
            $isExists = $user->meetingRequests()
                ->where('property_type', $validated['property_type'])
                ->where('property_id', $validated['property_id'])
                ->where('status', '!=', 'completed')
                ->exists();

            if ($isExists) {
                return $this->forbiddenResponse('You have already created a meeting request for this property');
            }

            // Create meeting request
            $meetingRequest = MeetingRequest::create([
                'property_type' => $validated['property_type'],
                'property_id' => $validated['property_id'],
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_phone' => $validated['user_phone'] ?? null,
                'preferred_time' => $validated['preferred_time'],
                'message' => $validated['message'] ?? null,
                'status' => 'pending',
            ]);

            // Send notification to user
            $user->notify(new MeetingRequestCreated($meetingRequest));

            return $this->createdResponse('Meeting request created successfully', $meetingRequest);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Display the specified meeting request.
     */
    public function show(string $id)
    {
        try {
            $meetingRequest = MeetingRequest::findOrFail($id);

            // Check if user is authorized to view this meeting request
            $user = Auth::user();
            if ($user->role !== 'admin' && $user->email !== $meetingRequest->user_email) {
                return $this->forbiddenResponse('You are not authorized to view this meeting request');
            }

            return $this->successResponse('Meeting request retrieved successfully', $meetingRequest);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update the specified meeting request status (admin only).
     */
    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'status' => ['required', 'string', 'in:pending,scheduled,cancelled,completed'],
                'preferred_time' => ['nullable', 'date', 'after:now'],
            ]);

            $meetingRequest = MeetingRequest::findOrFail($id);
            $previousStatus = $meetingRequest->status;

            // Only admins can update meeting requests
            if (!$request->user()->hasRole('admin')) {
                return $this->forbiddenResponse('You are not authorized to update meeting requests');
            }

            $meetingRequest->status = $validated['status'];

            if (isset($validated['preferred_time'])) {
                $meetingRequest->preferred_time = $validated['preferred_time'];
            }

            $meetingRequest->save();

            // Send notification to user about the status update
            if ($previousStatus !== $meetingRequest->status) {
                $user = User::where('email', $meetingRequest->user_email)->first();
                if ($user) {
                    $user->notify(new MeetingRequestUpdated($meetingRequest, $previousStatus));
                }
            }

            return $this->successResponse('Meeting request updated successfully', $meetingRequest);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Allow users to cancel their own meeting requests.
     */
    public function cancel(Request $request, string $id)
    {
        try {
            $meetingRequest = MeetingRequest::findOrFail($id);

            // Check if user is authorized to cancel this meeting request
            $user = Auth::user();
            if ($user->email !== $meetingRequest->user_email) {
                return $this->forbiddenResponse('You are not authorized to cancel this meeting request');
            }

            // Can only cancel if not already completed
            if ($meetingRequest->status === 'completed') {
                return $this->errorResponse('Cannot cancel a completed meeting request');
            }

            $previousStatus = $meetingRequest->status;
            $meetingRequest->status = 'cancelled';
            $meetingRequest->save();

            // Send notification to user about cancellation
            $user->notify(new MeetingRequestUpdated($meetingRequest, $previousStatus));

            return $this->successResponse('Meeting request cancelled successfully', $meetingRequest);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Remove the specified meeting request from storage (admin only).
     */
    public function destroy(Request $request, string $id)
    {
        try {
            // Only admins can delete meeting requests
            if (!$request->user()->hasRole('admin')) {
                return $this->forbiddenResponse('You are not authorized to delete meeting requests');
            }

            $meetingRequest = MeetingRequest::findOrFail($id);
            $meetingRequest->delete();

            return $this->noContentResponse();
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get user's upcoming meetings
     */
    public function upcoming(Request $request)
    {
        try {
            $user = Auth::user();

            $meetings = MeetingRequest::where('user_email', $user->email)
                ->whereIn('status', ['pending', 'scheduled'])
                ->where('preferred_time', '>', now())
                ->orderBy('preferred_time')
                ->get();

            return $this->successResponse('Upcoming meetings retrieved successfully', $meetings);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}
