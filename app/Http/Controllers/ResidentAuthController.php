<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Traits\ApiResponse;
use App\Traits\ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;

class ResidentAuthController extends Controller
{
    use ApiResponse, ExceptionHandler;


    /**
     * Get authenticated resident profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $resident = $request->user();

            return $this->successResponse(
                'Resident profile retrieved successfully',
                $resident->load('properties')
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
