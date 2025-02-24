<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Floor;
use Illuminate\Http\Request;

class FloorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Apartment::query();

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
    public function show(string $id)
    {
        //
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
