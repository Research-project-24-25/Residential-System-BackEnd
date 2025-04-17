<?php

namespace App\Http\Controllers;

use App\Filters\PropertyFilter;
use App\Http\Requests\PropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Apartment;
use App\Models\House;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Throwable;

class PropertyController extends BaseController
{
  /**
   * The property filter instance
   */
  protected PropertyFilter $propertyFilter;

  /**
   * Create a new controller instance
   */
  public function __construct(PropertyFilter $propertyFilter)
  {
    $this->propertyFilter = $propertyFilter;
  }

  /**
   * List all properties with filtering, searching and sorting
   */
  public function index(PropertyRequest $request): ResourceCollection|JsonResponse
  {
    try {
      $perPage = (int) ($request->input('per_page') ?? 15);
      $page = (int) ($request->input('page') ?? 1);
      $propertyType = $request->input('property_type');

      // Get property collections based on type filter
      if ($propertyType === 'apartment') {
        $properties = $this->propertyFilter->filterApartments($request);
        $paginatedProperties = $this->paginateCollection($properties, $perPage, $page);
      } elseif ($propertyType === 'house') {
        $properties = $this->propertyFilter->filterHouses($request);
        $paginatedProperties = $this->paginateCollection($properties, $perPage, $page);
      } else {
        // Get both property types and combine them
        $apartments = $this->propertyFilter->filterApartments($request);
        $houses = $this->propertyFilter->filterHouses($request);

        // Combine and sort the collections
        $allProperties = $this->propertyFilter->combineAndSort($apartments, $houses, $request);
        $paginatedProperties = $this->paginateCollection($allProperties, $perPage, $page);
      }

      return PropertyResource::collection($paginatedProperties);
    } catch (Throwable $e) {
      return $this->handleException($e);
    }
  }

  /**
   * Show a specific property
   */
  public function show(string $type, $id): JsonResponse
  {
    try {
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

  /**
   * Find a property by type and ID
   */
  private function findProperty(string $propertyType, $id)
  {
    return match ($propertyType) {
      'apartment' => Apartment::with(['floor.building', 'residents'])->find($id),
      'house' => House::with('residents')->find($id),
      default => null,
    };
  }

  /**
   * Paginate a collection
   */
  private function paginateCollection(Collection $collection, int $perPage, int $page): LengthAwarePaginator
  {
    return new LengthAwarePaginator(
      $collection->forPage($page, $perPage),
      $collection->count(),
      $perPage,
      $page,
      ['path' => request()->url()]
    );
  }
}
