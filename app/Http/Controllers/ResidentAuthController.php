<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ResidentAuthController extends Controller
{
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
