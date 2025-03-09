<?php

namespace App\Http\Controllers;

use App\Http\Resources\FloorResource;
use App\Models\Floor;
use App\Traits\Filterable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\JsonResponse;

class FloorController extends Controller
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
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'building_id' => ['required', 'exists:buildings,id'],
            'floor_number' => ['required', 'integer'],
            'total_apartments' => ['required', 'integer', 'min:1'],
        ]);

        $floor = Floor::create($validated);

        return response()->json([
            'message' => 'Floor created successfully.',
            'data' => new FloorResource($floor)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Floor $floor): FloorResource
    {
        return new FloorResource($floor->load(['building', 'apartments']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Floor $floor): JsonResponse
    {
        $validated = $request->validate([
            'building_id' => ['required', 'exists:buildings,id'],
            'floor_number' => ['required', 'integer'],
            'total_apartments' => ['required', 'integer', 'min:1'],
        ]);

        $floor->update($validated);

        return response()->json([
            'message' => 'Floor updated successfully.',
            'data' => new FloorResource($floor)
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Floor $floor): JsonResponse
    {
        $floor->delete();

        return response()->json([
            'message' => 'Floor deleted successfully.'
        ], 200);
    }
}
