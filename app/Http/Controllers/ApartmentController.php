<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use Illuminate\Http\Request;

class ApartmentController extends Controller
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
        $query = Apartment::query()->with(['floor.building']);

        if ($request->filled('floor_id')) {
            $query->where('floor_id', $request->floor_id);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('apartment_number', 'LIKE', "%{$searchTerm}%");
            });
        }

        $sort = $request->get('sort', 'apartment_number');
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
            'floor_id' => ['required', 'exists:floors,id'],
            'apartment_number' => ['required', 'string'],
        ]);

        Apartment::create($validated);

        return response()->json(['message' => 'Apartment created successfully.'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Apartment $apartment)
    {
        return response()->json($apartment->load('floor.building'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Apartment $apartment)
    {
        $validated = $request->validate([
            'floor_id' => ['required', 'integer'],
            'apartment_number' => ['required'],
        ]);

        $apartment->update($validated);

        return response()->json(['message' => 'Apartment updated successfully.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Apartment $apartment)
    {
        $apartment->delete();

        return response()->json(['message' => 'Apartment deleted successfully.']);
    }
}
