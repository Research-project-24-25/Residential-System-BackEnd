<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\House;
use App\Models\MeetingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail; // Placeholder for Mail facade
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;
class MeetingRequestController extends BaseController
{
    /**
     * Store a newly created meeting request in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'property_type' => ['required', Rule::in(['apartment', 'house'])],
                'property_id' => ['required', 'integer'],
                'user_name' => ['required', 'string', 'max:255'],
                'user_email' => ['required', 'email', 'max:255'],
                'user_phone' => ['nullable', 'string', 'max:50'],
                'preferred_time' => ['nullable', 'date'],
                'message' => ['nullable', 'string', 'max:5000'],
            ]);

            // Verify property exists
            $property = match ($validated['property_type']) {
                'apartment' => Apartment::find($validated['property_id']),
                'house' => House::find($validated['property_id']),
            };

            if (!$property) {
                return $this->notFoundResponse('The selected property does not exist.');
            }

            // Generate unique token
            $token = Str::uuid()->toString(); // Using UUID for simplicity

            $meetingRequest = MeetingRequest::create([
                'property_type' => $validated['property_type'],
                'property_id' => $validated['property_id'],
                'user_name' => $validated['user_name'],
                'user_email' => $validated['user_email'],
                'user_phone' => $validated['user_phone'] ?? null,
                'preferred_time' => $validated['preferred_time'] ?? null,
                'message' => $validated['message'] ?? null,
                'status' => 'pending_verification',
                'verification_token' => $token,
            ]);

            // --- Send Verification Email ---
            // TODO: Implement sending email using Laravel Mail/Notifications
            // Example: Mail::to($validated['user_email'])->send(new VerifyMeetingRequestMail($meetingRequest, $token));
            // Log::info("Verification email needs to be sent for token: {$token}"); // Temporary log

            return $this->createdResponse('Meeting request received. Please check your email to verify your request.');

        } catch (Throwable $e) {
            // Assuming handleException exists in BaseController
            return $this->handleException($e);
        }
    }

    /**
     * Verify the meeting request using the provided token.
     */
    public function verify(string $token): JsonResponse
    {
        try {
            $meetingRequest = MeetingRequest::where('verification_token', $token)->first();

            if (!$meetingRequest) {
                return $this->notFoundResponse('Invalid or expired verification link.');
            }

            if ($meetingRequest->status !== 'pending_verification') {
                 // Could be already verified or cancelled
                 return $this->errorResponse('This request cannot be verified or has already been verified.', 400);
            }

            // Mark as verified
            $meetingRequest->status = 'verified';
            $meetingRequest->verified_at = now();
            $meetingRequest->verification_token = null; // Clear the token
            $meetingRequest->save();

            // --- Send Confirmation/Notification ---
            // TODO: Optionally send confirmation to user and/or notification to admin
            // Example: Mail::to($meetingRequest->user_email)->send(new MeetingRequestVerifiedMail($meetingRequest));
            // Example: Notification::route('mail', 'admin@example.com')->notify(new NewMeetingRequestNotification($meetingRequest));

            return $this->successResponse('Your meeting request has been successfully verified.');

        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
