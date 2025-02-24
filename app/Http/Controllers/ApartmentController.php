<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use Illuminate\Http\Request;

class ApartmentController extends Controller
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
            'floor_id' => ['required', 'integer'],
            'apartment_number' => ['required'],
        ]);

        Apartment::create($validated);

        return response()->json(['message' => 'Apartment created successfully.'], 201);
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
