<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResidentRequest;
use App\Http\Resources\ResidentResource;
use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Throwable;

class ResidentController extends BaseController
{

    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin')->except(['show']);
    }

    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $query = Resident::query()
                ->with(['house', 'apartment.floor.building', 'createdBy']);

            // Apply search filtering
            $this->applySearch($query, $request, [
                'first_name',
                'last_name',
                'email',
                'phone_number'
            ]);

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filter by property type
            if ($request->filled('property_type')) {
                if ($request->property_type === 'house') {
                    $query->whereNotNull('house_id');
                } elseif ($request->property_type === 'apartment') {
                    $query->whereNotNull('apartment_id');
                }
            }

            // Apply sorting
            $this->applySorting($query, $request);

            // Paginate and return resources
            $residents = $this->applyPagination($query, $request);

            return ResidentResource::collection($residents);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function store(ResidentRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Hash the password
            $validated['password'] = Hash::make($validated['password']);

            // Add the admin who created this resident
            $validated['created_by'] = $request->user()->id;

            $resident = Resident::create($validated);

            return $this->createdResponse(
                'Resident created successfully',
                new ResidentResource($resident->load(['house', 'apartment.floor.building', 'createdBy']))
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function show(Request $request, Resident $resident): JsonResponse
    {
        try {
            // Only allow admins or the resident themselves to view
            if (
                !($request->user() instanceof \App\Models\Admin) &&
                !($request->user() instanceof \App\Models\Resident && $request->user()->id === $resident->id)
            ) {
                return $this->forbiddenResponse();
            }

            return $this->successResponse(
                'Resident retrieved successfully',
                new ResidentResource($resident->load(['house', 'apartment.floor.building', 'createdBy']))
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function update(ResidentRequest $request, Resident $resident): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Hash the password if it's provided
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $resident->update($validated);

            return $this->successResponse(
                'Resident updated successfully',
                new ResidentResource($resident->load(['house', 'apartment.floor.building', 'createdBy']))
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function destroy(Resident $resident): JsonResponse
    {
        try {
            $resident->delete();
            return $this->successResponse('Resident deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
