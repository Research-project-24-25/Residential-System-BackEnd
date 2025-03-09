<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResidentRequest;
use App\Http\Resources\ResidentResource;
use App\Models\Resident;
use App\Traits\Filterable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class ResidentController extends BaseController
{
    use Filterable;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin')->except(['show']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): ResourceCollection
    {
        $query = Resident::query()
            ->with(['house', 'apartment.floor.building', 'createdBy']);

        // Apply search filtering
        $this->applySearch($query, $request, [
            'first_name',
            'last_name',
            'email',
            'phone_number'
        ]);

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

        // Apply sorting
        $this->applySorting($query, $request);

        // Paginate and return resources
        $residents = $this->applyPagination($query, $request);

        return ResidentResource::collection($residents);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ResidentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Hash the password
        $validated['password'] = Hash::make($validated['password']);

        // Add the admin who created this resident
        $validated['created_by'] = $request->user()->id;

        $resident = Resident::create($validated);

        return response()->json([
            'message' => 'Resident created successfully',
            'data' => new ResidentResource($resident->load(['house', 'apartment.floor.building', 'createdBy']))
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Resident $resident): JsonResponse
    {
        // Only allow admins or the resident themselves to view
        if (
            !($request->user() instanceof \App\Models\Admin) &&
            !($request->user() instanceof \App\Models\Resident && $request->user()->id === $resident->id)
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(
            new ResidentResource($resident->load(['house', 'apartment.floor.building', 'createdBy']))
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ResidentRequest $request, Resident $resident): JsonResponse
    {
        $validated = $request->validated();

        // Hash the password if it's provided
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $resident->update($validated);

        return response()->json([
            'message' => 'Resident updated successfully',
            'data' => new ResidentResource($resident->load(['house', 'apartment.floor.building', 'createdBy']))
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Resident $resident): JsonResponse
    {
        $resident->delete();

        return response()->json([
            'message' => 'Resident deleted successfully'
        ]);
    }
}
