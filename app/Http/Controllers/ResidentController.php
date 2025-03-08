<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use Illuminate\Http\Request;

class ResidentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Resident::query()
            ->with(['house', 'apartment.floor.building', 'createdBy']);

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('phone_number', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by property type
        if ($request->filled('property_type')) {
            if ($request->property_type === 'house') {
                $query->whereNotNull('house_id');
            } elseif ($request->property_type === 'apartment') {
                $query->whereNotNull('apartment_id');
            }
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
            'username' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:residents'],
            'phone_number' => ['required', 'string'],
            'age' => ['required', 'integer', 'min:0'],
            'gender' => ['required', 'in:male,female'],
            'status' => ['sometimes', 'in:active,inactive'],
            'house_id' => ['nullable', 'exists:houses,id'],
            'apartment_id' => ['nullable', 'exists:apartments,id'],
        ]);

        // Ensure resident is assigned to either a house or an apartment, not both
        if (($validated['house_id'] ?? null) && ($validated['apartment_id'] ?? null)) {
            return response()->json([
                'message' => 'A resident cannot be assigned to both a house and an apartment'
            ], 422);
        }

        // Add the admin who created this resident
        $validated['created_by'] = $request->user()->id;

        $resident = Resident::create($validated);

        return response()->json([
            'message' => 'Resident created successfully',
            'resident' => $resident->load(['house', 'apartment.floor.building', 'createdBy'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Resident $resident)
    {
        return response()->json(
            $resident->load(['house', 'apartment.floor.building', 'createdBy'])
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Resident $resident)
    {
        $validated = $request->validate([
            'username' => ['sometimes', 'string', 'max:255'],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:residents,email,' . $resident->id],
            'phone_number' => ['sometimes', 'string'],
            'age' => ['sometimes', 'integer', 'min:0'],
            'gender' => ['sometimes', 'in:male,female'],
            'status' => ['sometimes', 'in:active,inactive'],
            'house_id' => ['nullable', 'exists:houses,id'],
            'apartment_id' => ['nullable', 'exists:apartments,id'],
        ]);

        // Ensure resident is assigned to either a house or an apartment, not both
        if (($validated['house_id'] ?? null) && ($validated['apartment_id'] ?? null)) {
            return response()->json([
                'message' => 'A resident cannot be assigned to both a house and an apartment'
            ], 422);
        }

        $resident->update($validated);

        return response()->json([
            'message' => 'Resident updated successfully',
            'resident' => $resident->load(['house', 'apartment.floor.building', 'createdBy'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Resident $resident)
    {
        $resident->delete();

        return response()->json([
            'message' => 'Resident deleted successfully'
        ]);
    }
}
