<?php

namespace App\Http\Controllers;

use App\Models\House;
use Illuminate\Http\Request;

class HouseController extends Controller
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
        $query = House::query();

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('house_number', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('house_type', 'LIKE', "%{$searchTerm}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('house_type', $request->type);
        }

        if ($request->filled('is_occupied')) {
            $query->where('is_occupied', $request->boolean('is_occupied'));
        }

        $sort = $request->get('sort', 'house_number');
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
            'house_number' => ['required', 'string'],
            'number_of_residents' => ['required', 'integer', 'min:0'],
            'house_type' => ['required', 'in:villa,house'],
            'is_occupied' => ['boolean'],
        ]);

        House::create($validated);

        return response()->json(['message' => 'House created successfully.'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(House $house)
    {
        return response()->json($house);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, House $house)
    {
        $validated = $request->validate([
            'house_number' => ['required', 'string'],
            'number_of_residents' => ['required', 'integer', 'min:0'],
            'house_type' => ['required', 'in:villa,house'],
            'is_occupied' => ['boolean'],
        ]);

        $house->update($validated);

        return response()->json(['message' => 'House updated successfully.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(House $house)
    {
        $house->delete();

        return response()->json(['message' => 'House deleted successfully.']);
    }
}
