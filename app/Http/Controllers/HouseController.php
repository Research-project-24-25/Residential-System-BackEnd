<?php

namespace App\Http\Controllers;

use App\Http\Resources\HouseResource;
use App\Models\House;
use App\Traits\Filterable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\JsonResponse;

class HouseController extends Controller
{
    use Filterable;

    public function __construct()
    {
        // Allow public access to index and show methods
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): ResourceCollection
    {
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
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'house_number' => ['required', 'string'],
            'number_of_residents' => ['required', 'integer', 'min:0'],
            'house_type' => ['required', 'in:villa,house'],
            'is_occupied' => ['boolean'],
        ]);

        $house = House::create($validated);

        return response()->json([
            'message' => 'House created successfully.',
            'data' => new HouseResource($house)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(House $house): HouseResource
    {
        return new HouseResource($house->load('residents'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, House $house): JsonResponse
    {
        $validated = $request->validate([
            'house_number' => ['required', 'string'],
            'number_of_residents' => ['required', 'integer', 'min:0'],
            'house_type' => ['required', 'in:villa,house'],
            'is_occupied' => ['boolean'],
        ]);

        $house->update($validated);

        return response()->json([
            'message' => 'House updated successfully.',
            'data' => new HouseResource($house)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(House $house): JsonResponse
    {
        $house->delete();

        return response()->json([
            'message' => 'House deleted successfully.'
        ]);
    }
}
