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
    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $query = Floor::query()->withCount('apartments');

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
                'floor_number' => ['required', 'integer', 'unique:floors,floor_number,NULL,id,building_id,' . $request->building_id],
            ]);
            $floor = Floor::create($validated);

            return $this->createdResponse(
                'Floor created successfully',
                new FloorResource($floor->loadCount('apartments'))
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $floor = Floor::query()
                ->withCount('apartments')
                ->with(['building', 'apartments'])
                ->findOrFail($id);

            return $this->successResponse(
                'Floor retrieved successfully',
                new FloorResource($floor)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function update($id, Request $request): JsonResponse
    {
        try {
            $floor = Floor::withCount('apartments')->findOrFail($id);

            $validated = $request->validate([
                'building_id' => ['sometimes', 'exists:buildings,id'],
                'floor_number' => ['sometimes', 'integer', 'unique:floors,floor_number,' . $id . ',id,building_id,' . $request->building_id],
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

    public function destroy($id): JsonResponse
    {
        try {
            $floor = Floor::findOrFail($id);
            $floor->delete();
            return $this->successResponse('Floor deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
