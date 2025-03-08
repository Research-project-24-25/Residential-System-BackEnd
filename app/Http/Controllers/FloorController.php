<?php

namespace App\Http\Controllers;

use App\Models\Floor;
use Illuminate\Http\Request;

class FloorController extends Controller
{
    public function __construct()
    {
        // Allow public access to index and show methods
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Floor::query()->with(['building', 'apartments']);

        if ($request->filled('building_id')) {
            $query->where('building_id', $request->building_id);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('floor_number', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('total_apartments', 'LIKE', "%{$searchTerm}%");
            });
        }

        $sort = $request->get('sort', 'floor_number');
        $direction = $request->get('direction', 'asc');

        $results = $query->orderBy($sort, $direction)
            ->paginate(10);

        return response()->json($results);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'building_id' => ['required', 'exists:buildings,id'],
            'floor_number' => ['required', 'integer'],
            'total_apartments' => ['required', 'integer', 'min:1'],
        ]);

        Floor::create($validated);

        return response()->json(['message' => 'Floor created successfully.'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Floor $floor)
    {
        return response()->json($floor->load(['building', 'apartments']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Floor $floor)
    {
        $validated = $request->validate([
            'building_id' => ['required', 'exists:buildings,id'],
            'floor_number' => ['required', 'integer'],
            'total_apartments' => ['required', 'integer', 'min:1'],
        ]);

        $floor->update($validated);

        return response()->json(['message' => 'Floor updated successfully.'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Floor $floor)
    {
        $floor->delete();

        return response()->json(['message' => 'Floor deleted successfully.'], 200);
    }
}
