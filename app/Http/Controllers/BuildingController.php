<?php

namespace App\Http\Controllers;

use App\Http\Resources\BuildingResource;
use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\JsonResponse;
use Throwable;

class BuildingController extends BaseController
{
    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $query = Building::query()->withCount('floors');
            // Apply search filtering
            $this->applySearch($query, $request, [
                'name',
                'address',
            ]);

            // Apply sorting
            $this->applySorting($query, $request, 'created_at', 'desc');

            // Paginate and return resources
            $buildings = $this->applyPagination($query, $request);

            return BuildingResource::collection($buildings);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['required'],
                'address' => ['required'],
            ]);
            $building = Building::create($validated);

            return $this->createdResponse(
                'Building created successfully',
                new BuildingResource($building->loadCount('floors'))
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $building = Building::query()
                ->withCount('floors')
                ->findOrFail($id);

            return $this->successResponse(
                'Building retrieved successfully',
                new BuildingResource($building)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function update($id, Request $request): JsonResponse
    {
        try {
            $building = Building::query()
                ->withCount('floors')
                ->findOrFail($id);

            $validated = $request->validate([
                'name' => ['sometimes'],
                'address' => ['sometimes'],
            ]);

            $building->update($validated);

            return $this->successResponse(
                'Building updated successfully',
                new BuildingResource($building)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $building = Building::findOrFail($id);
            $building->delete();
            return $this->successResponse('Building deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
