<?php

namespace App\Http\Controllers;

use App\Models\Building;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Building::all();
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
    public function show(string $id)
    {
        //
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
        return response()->json(['message' => 'Building updated successfully.'], 200);
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
