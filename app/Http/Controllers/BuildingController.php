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
                new BuildingResource($building)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function show(Building $building): JsonResponse
    {
        try {
            return $this->successResponse(
                'Building retrieved successfully',
                new BuildingResource(
                    $building->loadCount('floors')
                        ->load('floors.apartments')
                )
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function update(Request $request, Building $building): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['required'],
                'address' => ['required'],
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

    public function destroy(Building $building): JsonResponse
    {
        try {
            $building->delete();
            return $this->successResponse('Building deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
