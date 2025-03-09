<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApartmentResource;
use App\Models\Apartment;
use App\Traits\Filterable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\JsonResponse;

class ApartmentController extends Controller
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
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'floor_id' => ['required', 'exists:floors,id'],
            'apartment_number' => ['required', 'string'],
        ]);

        $apartment = Apartment::create($validated);

        return response()->json([
            'message' => 'Apartment created successfully.',
            'data' => new ApartmentResource($apartment)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Apartment $apartment): ApartmentResource
    {
        return new ApartmentResource($apartment->load(['floor.building', 'residents']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Apartment $apartment): JsonResponse
    {
        $validated = $request->validate([
            'floor_id' => ['required', 'exists:floors,id'],
            'apartment_number' => ['required', 'string'],
        ]);

        $apartment->update($validated);

        return response()->json([
            'message' => 'Apartment updated successfully.',
            'data' => new ApartmentResource($apartment)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Apartment $apartment): JsonResponse
    {
        $apartment->delete();

        return response()->json([
            'message' => 'Apartment deleted successfully.'
        ]);
    }
}
