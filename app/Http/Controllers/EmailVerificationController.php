<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
  public function verify(Request $request, $id, $hash)
  {
    $user = User::findOrFail($id);

    abort_unless(
      hash_equals((string) $hash, sha1($user->getEmailForVerification())),
      403,
      'Invalid verification link.'
    );

    if ($user->hasVerifiedEmail()) {
      return response()->json(['message' => 'Email already verified.']);
    }

    $user->markEmailAsVerified();
    return response()->json(['message' => 'Email verified successfully.']);
  }

  public function resend(Request $request)
  {
    if ($request->user()->hasVerifiedEmail()) {
      return response()->json(['message' => 'Email already verified.']);
    }

    $request->user()->sendEmailVerificationNotification();

    return response()->json(['message' => 'Verification link sent.']);
  }
}
