<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;
use App\Models\User;
use App\Traits\ApiResponse;

class EmailVerificationController extends Controller
{
  use ApiResponse;

  /**
   * Mark the authenticated user's email address as verified.
   */
  public function verify(Request $request, $id, $hash)
  {
    $user = User::findOrFail($id);

    // Check if URL is valid
    if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
      return $this->handleVerificationResponse(false, 'Invalid verification link');
    }

    // Check if already verified
    if ($user->hasVerifiedEmail()) {
      return $this->handleVerificationResponse(true, 'Email already verified');
    }

    // Mark email as verified
    if ($user->markEmailAsVerified()) {
      event(new Verified($user));
    }

    return $this->handleVerificationResponse(true, 'Email has been verified');
  }

  /**
   * Resend the email verification notification.
   */
  public function resend(Request $request)
  {
    if ($request->user()->hasVerifiedEmail()) {
      return $this->successResponse('Email already verified');
    }

    $request->user()->sendEmailVerificationNotification();

    return $this->successResponse('Verification link sent');
  }

  /**
   * Get the verification status.
   */
  public function status(Request $request)
  {
    return $this->successResponse('User verification status', [
      'verified' => $request->user()->hasVerifiedEmail(),
      'email' => $request->user()->email
    ]);
  }

  /**
   * Handle the verification response for both API and web clients.
   */
  protected function handleVerificationResponse(bool $success, string $message)
  {
    $status = $success ? 'success' : 'error';
    $frontendUrl = config('app.frontend_url');
    $redirectUrl = "{$frontendUrl}/email-verification?status={$status}&message=" . urlencode($message);

    // For API clients
    if (request()->wantsJson()) {
      return $this->successResponse($message, ['verified' => $success]);
    }

    // For web clients (redirect to frontend)
    return redirect()->away($redirectUrl);
  }
}
