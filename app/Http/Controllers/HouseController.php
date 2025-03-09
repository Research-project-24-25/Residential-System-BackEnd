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
    public function __construct()
    {
        // Allow public access to index and show methods
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $query = House::query()->with('residents');

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
                'house_number' => ['required', 'string'],
                'number_of_residents' => ['required', 'integer', 'min:0'],
                'house_type' => ['required', 'in:villa,house'],
                'is_occupied' => ['boolean'],
            ]);
            $house = House::create($validated);

            return $this->createdResponse(
                'House created successfully',
                new HouseResource($house)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function show(House $house): JsonResponse
    {
        try {
            return $this->successResponse(
                'House retrieved successfully',
                new HouseResource($house->load('residents'))
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function update(Request $request, House $house): JsonResponse
    {
        try {
            $validated = $request->validate([
                'house_number' => ['required', 'string'],
                'number_of_residents' => ['required', 'integer', 'min:0'],
                'house_type' => ['required', 'in:villa,house'],
                'is_occupied' => ['boolean'],
            ]);
            $house->update($validated);

            return $this->successResponse(
                'House updated successfully',
                new HouseResource($house)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function destroy(House $house): JsonResponse
    {
        try {
            $house->delete();
            return $this->successResponse('House deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
