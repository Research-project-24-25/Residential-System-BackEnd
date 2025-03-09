<?php

namespace App\Http\Controllers;

use App\Http\Resources\BuildingResource;
use App\Models\Building;
use App\Traits\Filterable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\JsonResponse;

class BuildingController extends Controller
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
        $query = Building::query()->with('floors');  // Eager load floors

        // Apply search filtering
        $this->applySearch($query, $request, [
            'name',
            'address',
            'total_floors'
        ]);

        // Apply sorting
        $this->applySorting($query, $request, 'created_at', 'desc');

        // Paginate and return resources
        $buildings = $this->applyPagination($query, $request);

        return BuildingResource::collection($buildings);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required'],
            'address' => ['required'],
            'total_floors' => ['required', 'integer'],
        ]);

        $building = Building::create($validated);

        return response()->json([
            'message' => 'Building created successfully.',
            'data' => new BuildingResource($building)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Building $building): BuildingResource
    {
        return new BuildingResource($building->load('floors.apartments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Building $building): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required'],
            'address' => ['required'],
            'total_floors' => ['required', 'integer', 'min:1'],
        ]);

        $building->update($validated);

        return response()->json([
            'message' => 'Building updated successfully.',
            'data' => new BuildingResource($building)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Building $building): JsonResponse
    {
        $building->delete();

        return response()->json([
            'message' => 'Building deleted successfully.'
        ], 200);
    }
}
