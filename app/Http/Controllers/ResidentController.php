<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResidentRequest;
use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ResidentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin')->except(['show']);
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
    public function store(ResidentRequest $request)
    {
        $validated = $request->validated();
        
        // Hash the password
        $validated['password'] = Hash::make($validated['password']);

        // Add the admin who created this resident
        $validated['created_by'] = $request->user()->id;

        $resident = Resident::create($validated);

        // Hide password in response
        $resident = $resident->makeHidden('password');

        return response()->json([
            'message' => 'Resident created successfully',
            'resident' => $resident->load(['house', 'apartment.floor.building', 'createdBy'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Resident $resident)
    {
        // Only allow admins or the resident themselves to view
        if (
            !($request->user() instanceof \App\Models\Admin) && 
            !($request->user() instanceof \App\Models\Resident && $request->user()->id === $resident->id)
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(
            $resident->makeHidden('password')->load(['house', 'apartment.floor.building', 'createdBy'])
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ResidentRequest $request, Resident $resident)
    {
        $validated = $request->validated();
        
        // Hash the password if it's provided
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $resident->update($validated);

        return response()->json([
            'message' => 'Resident updated successfully',
            'resident' => $resident->makeHidden('password')->load(['house', 'apartment.floor.building', 'createdBy'])
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