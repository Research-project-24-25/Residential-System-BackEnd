<?php

namespace App\Http\Controllers;

use App\Http\Resources\FloorResource;
use App\Models\Floor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\JsonResponse;
use Throwable;

class FloorController extends BaseController
{
    public function __construct()
    {
        // Allow public access to index and show methods
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $query = Floor::query()->with(['building', 'apartments']);

            if ($request->filled('building_id')) {
                $query->where('building_id', $request->building_id);
            }

            // Apply search filtering
            $this->applySearch($query, $request, [
                'floor_number',
                'total_apartments'
            ]);

            // Apply sorting
            $this->applySorting($query, $request, 'floor_number', 'asc');

            // Paginate and return resources
            $floors = $this->applyPagination($query, $request);

            return FloorResource::collection($floors);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'building_id' => ['required', 'exists:buildings,id'],
                'floor_number' => ['required', 'integer'],
                'total_apartments' => ['required', 'integer', 'min:1'],
            ]);
            $floor = Floor::create($validated);

            return $this->createdResponse(
                'Floor created successfully',
                new FloorResource($floor)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function show(Floor $floor): JsonResponse
    {
        try {
            return $this->successResponse(
                'Floor retrieved successfully',
                new FloorResource($floor->load(['building', 'apartments']))
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function update(Request $request, Floor $floor): JsonResponse
    {
        try {
            $validated = $request->validate([
                'building_id' => ['required', 'exists:buildings,id'],
                'floor_number' => ['required', 'integer'],
                'total_apartments' => ['required', 'integer', 'min:1'],
            ]);
            $floor->update($validated);

            return $this->successResponse(
                'Floor updated successfully',
                new FloorResource($floor)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function destroy(Floor $floor): JsonResponse
    {
        try {
            $floor->delete();
            return $this->successResponse('Floor deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
