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
  public function index(Request $request): ResourceCollection|JsonResponse
  {
    try {
      $propertyType = $request->input('type', 'all');

      return match ($propertyType) {
        'house' => $this->getHouseProperties($request),
        'apartment' => $this->getApartmentProperties($request),
        default => $this->getAllProperties($request)
      };
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
    // Assuming applySearch/applySorting exist in BaseController and handle request params
    $this->applySearch($query, $request, ['identifier', 'description']); // Search identifier and description
    $this->applySorting($query, $request, 'identifier'); // Default sort by identifier
    return PropertyResource::collection($this->applyPagination($query, $request));
  }

  private function getApartmentProperties(Request $request): ResourceCollection
  {
    // This method seems redundant if getAllProperties handles combined logic.
    // We will remove it later if getAllProperties is sufficient.
    // For now, just update field names for consistency if called directly.
    $query = Apartment::query()->with(['floor.building', 'residents']);
    // Assuming applySearch/applySorting exist in BaseController and handle request params
    $this->applySearch($query, $request, ['number', 'description']); // Search number and description
    // Also search building identifier
    if ($request->filled('search')) {
        $query->orWhereHas('floor.building', function ($q) use ($request) {
            $q->where('identifier', 'LIKE', "%{$request->search}%");
        });
    }
    $this->applySorting($query, $request, 'number'); // Default sort by number
    return PropertyResource::collection($this->applyPagination($query, $request));
  }

  private function getAllProperties(Request $request): ResourceCollection
  {
    $apartments = $this->getApartmentQuery($request)->get();
    $houses = $this->getHouseQuery($request)->get();
    $allProperties = $apartments->concat($houses);

    $page = $request->input('page', 1);
    $perPage = (int)$request->input('per_page', 15);
    $items = $allProperties->forPage($page, $perPage);

    return PropertyResource::collection($items)->additional([
      'meta' => [
        'total' => $allProperties->count(),
        'per_page' => $perPage,
        'current_page' => $page,
        'last_page' => ceil($allProperties->count() / $perPage)
      ]
    ]);
  }

  // Updated query builder for Apartments with filtering
  private function getApartmentQuery(Request $request)
  {
      $query = Apartment::query()->with(['floor.building', 'residents']);

      // --- Filtering ---
      if ($request->filled('status')) {
          $query->where('status', $request->status);
      }
      if ($request->filled('min_price')) {
          $query->where('price', '>=', $request->min_price);
      }
      if ($request->filled('max_price')) {
          $query->where('price', '<=', $request->max_price);
      }
      if ($request->filled('bedrooms')) {
          $query->where('bedrooms', $request->bedrooms);
      }
      // Add more filters as needed (area, features via JSON contains, etc.)

      // --- Searching ---
      if ($request->filled('search')) {
          $searchTerm = "%{$request->search}%";
          $query->where(function ($q) use ($searchTerm) {
              $q->where('number', 'LIKE', $searchTerm)
                ->orWhere('description', 'LIKE', $searchTerm)
                ->orWhereHas('floor.building', function ($bq) use ($searchTerm) {
                    $bq->where('identifier', 'LIKE', $searchTerm);
                });
          });
      }

      // --- Sorting ---
      // Assuming applySorting exists in BaseController
      $this->applySorting($query, $request, 'created_at', 'desc'); // Default sort

      return $query;
  }

  // Updated query builder for Houses with filtering
  private function getHouseQuery(Request $request)
  {
      $query = House::query()->with('residents');

      // --- Filtering ---
      if ($request->filled('status')) {
          $query->where('status', $request->status);
      }
      if ($request->filled('min_price')) {
          $query->where('price', '>=', $request->min_price);
      }
      if ($request->filled('max_price')) {
          $query->where('price', '<=', $request->max_price);
      }
      if ($request->filled('bedrooms')) {
          $query->where('bedrooms', $request->bedrooms);
      }
      if ($request->filled('property_style')) {
          $query->where('property_style', $request->property_style);
      }
      // Add more filters as needed (area, lot_size, features via JSON contains, etc.)


      // --- Searching ---
      if ($request->filled('search')) {
          $searchTerm = "%{$request->search}%";
          $query->where(function ($q) use ($searchTerm) {
              $q->where('identifier', 'LIKE', $searchTerm)
                ->orWhere('description', 'LIKE', $searchTerm);
          });
      }

      // --- Sorting ---
      // Assuming applySorting exists in BaseController
      $this->applySorting($query, $request, 'created_at', 'desc'); // Default sort

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
