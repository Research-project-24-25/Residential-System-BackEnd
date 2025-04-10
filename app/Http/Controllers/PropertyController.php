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
      return $this->getAllProperties($request);
    } catch (Throwable $e) {
      return $this->handleException($e);
    }
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
