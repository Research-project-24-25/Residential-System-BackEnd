<?php

namespace App\Http\Controllers;

use App\Http\Resources\HouseResource;
use App\Models\House;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\JsonResponse;
use Throwable;

class HouseController extends BaseController
{
    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $query = House::query()->withCount('residents');

            // Apply search filtering
            $this->applySearch($query, $request, [
                'house_number',
                'house_type'
            ]);

            if ($request->filled('type')) {
                $query->where('house_type', $request->type);
            }

            if ($request->filled('is_occupied')) {
                $query->where('is_occupied', $request->boolean('is_occupied'));
            }

            // Apply sorting
            $this->applySorting($query, $request, 'house_number', 'asc');

            // Paginate and return resources
            $houses = $this->applyPagination($query, $request);

            return HouseResource::collection($houses);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'house_number' => ['required', 'string', 'unique:houses,house_number'],
                'house_type' => ['required', 'in:villa,house'],
                'is_occupied' => ['boolean'],
            ]);
            $house = House::create($validated);

            return $this->createdResponse(
                'House created successfully',
                new HouseResource($house->loadCount('residents'))
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $house = House::with('residents')
                ->withCount('residents')
                ->findOrFail($id);
            return $this->successResponse(
                'House retrieved successfully',
                new HouseResource($house)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function update($id, Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'house_number' => ['sometimes', 'string', 'unique:houses,house_number,' . $id],
                'house_type' => ['sometimes', 'in:villa,house'],
                'is_occupied' => ['sometimes', 'boolean'],
            ]);
            $house = House::withCount('residents')
                ->findOrFail($id);
            $house->update($validated);

            return $this->successResponse(
                'House updated successfully',
                new HouseResource($house)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $house = House::findOrFail($id);
            $house->delete();
            return $this->successResponse('House deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
