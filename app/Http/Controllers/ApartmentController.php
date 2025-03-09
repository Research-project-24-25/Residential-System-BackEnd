<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApartmentResource;
use App\Models\Apartment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\JsonResponse;
use Throwable;

class ApartmentController extends BaseController
{
    public function __construct()
    {
        // Allow public access to index and show methods
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $query = Apartment::query()->with(['floor.building', 'residents']);

            if ($request->filled('floor_id')) {
                $query->where('floor_id', $request->floor_id);
            }

            // Apply search filtering
            $this->applySearch($query, $request, [
                'apartment_number'
            ]);

            // Apply sorting
            $this->applySorting($query, $request, 'apartment_number', 'asc');

            // Paginate and return resources
            $apartments = $this->applyPagination($query, $request);

            return ApartmentResource::collection($apartments);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'floor_id' => ['required', 'exists:floors,id'],
                'apartment_number' => ['required', 'string'],
            ]);
            $apartment = Apartment::create($validated);

            return $this->createdResponse(
                'Apartment created successfully',
                new ApartmentResource($apartment)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function show(Apartment $apartment): JsonResponse
    {
        try {
            return $this->successResponse(
                'Apartment retrieved successfully',
                new ApartmentResource($apartment->load(['floor.building', 'residents']))
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function update(Request $request, Apartment $apartment): JsonResponse
    {
        try {
            $validated = $request->validate([
                'floor_id' => ['required', 'exists:floors,id'],
                'apartment_number' => ['required', 'string'],
            ]);
            $apartment->update($validated);

            return $this->successResponse(
                'Apartment updated successfully',
                new ApartmentResource($apartment)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function destroy(Apartment $apartment): JsonResponse
    {
        try {
            $apartment->delete();
            return $this->successResponse('Apartment deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
