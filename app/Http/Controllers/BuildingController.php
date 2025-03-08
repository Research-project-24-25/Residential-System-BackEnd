<?php

namespace App\Http\Controllers;

use App\Models\Building;
use Illuminate\Http\Request;

class BuildingController extends Controller
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
        $query = Building::query()->with('floors');  // Eager load floors

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('address', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('total_floors', 'LIKE', "%{$searchTerm}%");
            });
        }

        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');

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
            'name' => ['required'],
            'address' => ['required'],
            'total_floors' => ['required', 'integer'],
        ]);

        Building::create($validated);

        return response()->json(['message' => 'Building created successfully.'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Building $building)
    {
        return response()->json($building->load('floors.apartments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Building $building)
    {
        $validated = $request->validate([
            'name' => ['required'],
            'address' => ['required'],
            'total_floors' => ['required', 'integer', 'min:1'],
        ]);

        $building->update($validated);

        return response()->json(['message' => 'Building updated successfully.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Building $building)
    {
        $building->delete();

        return response()->json(['message' => 'Building deleted successfully.'], 200);
    }
}
