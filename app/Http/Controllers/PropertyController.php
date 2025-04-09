<?php

namespace App\Http\Controllers;

use App\Http\Resources\PropertyResource;
use App\Models\Apartment;
use App\Models\House;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Throwable;

class PropertyController extends BaseController
{
  // Updated index method to handle filtering and searching across all properties
  public function index(Request $request): ResourceCollection|JsonResponse
  {
      try {
          // We will primarily use getAllProperties which handles filtering internally
          // The 'type' filter can be handled within the query builders if needed,
          // but the main endpoint usually shows a mix unless explicitly filtered.
          return $this->getAllProperties($request);
    } catch (Throwable $e) {
      return $this->handleException($e);
    }
  }

  private function getHouseProperties(Request $request): ResourceCollection
  {
    // This method seems redundant if getAllProperties handles combined logic.
    // We will remove it later if getAllProperties is sufficient.
    // For now, just update field names for consistency if called directly.
    $query = House::query()->with('residents');
    $this->applySearch($query, $request, ['identifier']);
    $this->applySorting($query, $request, 'identifier');
    return PropertyResource::collection($this->applyPagination($query, $request));
  }

  private function getApartmentProperties(Request $request): ResourceCollection
  {
    // This method seems redundant if getAllProperties handles combined logic.
    // We will remove it later if getAllProperties is sufficient.
    // For now, just update field names for consistency if called directly.
    $query = Apartment::query()->with(['floor.building', 'residents']);
    $this->applySearch($query, $request, ['number']);
    $query->orWhereHas('floor.building', function ($query) use ($request) {
      $query->where('identifier', 'LIKE', "%{$request->search}%");
    });
    $this->applySorting($query, $request, 'number');
    return PropertyResource::collection($this->applyPagination($query, $request));
  }

  // Updated method to combine filtered/searched Apartments and Houses
  private function getAllProperties(Request $request): ResourceCollection
  {
      // Get the filtered/searched query builders
      $apartmentQuery = $this->getApartmentQuery($request);
      $houseQuery = $this->getHouseQuery($request);

      // --- Combining Results ---
      // Fetch results separately
      $apartments = $apartmentQuery->get();
      $houses = $houseQuery->get();

      // Add a 'property_type' attribute to distinguish them easily in the resource/frontend
      $apartments->each(fn($apt) => $apt->property_type = 'apartment');
      $houses->each(fn($house) => $house->property_type = 'house');

      // Combine the collections
      $allProperties = $apartments->concat($houses);

      // --- Manual Pagination ---
      // Note: For very large datasets, consider a more optimized approach
      // like a UNION query or separate paginated results if performance becomes an issue.
      $page = $request->input('page', 1);
      $perPage = (int)$request->input('per_page', 15);
      $total = $allProperties->count();
      $items = $allProperties->forPage($page, $perPage)->values(); // Use values() to reset keys

      // Manually create a LengthAwarePaginator instance
      $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
          $items,
          $total,
          $perPage,
          $page,
          ['path' => $request->url(), 'query' => $request->query()]
      );

      // Return the collection using the paginator
      return PropertyResource::collection($paginator);
  }

  // Updated query builder for Apartments with filtering
  private function getApartmentQuery(Request $request)
  {
    $query = Apartment::query()->with(['floor.building', 'residents']);
    $this->applySearch($query, $request, ['number']);
    $query->orWhereHas('floor.building', function ($query) use ($request) {
      $query->where('identifier', 'LIKE', "%{$request->search}%");
    });
    $this->applySorting($query, $request, 'number');
    return $query;
  }

  // Updated query builder for Houses with filtering
  private function getHouseQuery(Request $request)
  {
    $query = House::query()->with('residents');
    $this->applySearch($query, $request, ['identifier']);
    $this->applySorting($query, $request, 'identifier');
    return $query;
  }

  // Updated signature to accept type and id from route parameters
  public function show(string $type, $id): JsonResponse
  {
    try {
      // $propertyType is now directly available as $type
      $property = $this->findProperty($type, $id);

      if (!$property) {
        return $this->notFoundResponse('Property not found');
      }

      return $this->successResponse(
        'Property retrieved successfully',
        new PropertyResource($property)
      );
    } catch (Throwable $e) {
      return $this->handleException($e);
    }
  }

  // Updated helper to use the type parameter reliably
  private function findProperty(string $propertyType, $id)
  {
    // Eager load relationships needed for display
    return match ($propertyType) {
        'apartment' => Apartment::with(['floor.building', 'residents'])->find($id),
        'house' => House::with('residents')->find($id),
        // No default case needed as the route enforces 'apartment' or 'house'
    };
  }
}
