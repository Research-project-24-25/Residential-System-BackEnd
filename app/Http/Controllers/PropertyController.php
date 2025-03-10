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
    $query = House::query()->with('residents');
    $this->applySearch($query, $request, ['house_number']);
    $this->applySorting($query, $request, 'house_number');
    return PropertyResource::collection($this->applyPagination($query, $request));
  }

  private function getApartmentProperties(Request $request): ResourceCollection
  {
    $query = Apartment::query()->with(['floor.building', 'residents']);
    $this->applySearch($query, $request, ['apartment_number']);
    $query->orWhereHas('floor.building', function ($query) use ($request) {
      $query->where('name', 'LIKE', "%{$request->search}%");
    });
    $this->applySorting($query, $request, 'apartment_number');
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

  private function getApartmentQuery(Request $request)
  {
    $query = Apartment::query()->with(['floor.building', 'residents']);
    $this->applySearch($query, $request, ['apartment_number']);
    $query->orWhereHas('floor.building', function ($query) use ($request) {
      $query->where('name', 'LIKE', "%{$request->search}%");
    });
    $this->applySorting($query, $request, 'apartment_number');
    return $query;
  }

  private function getHouseQuery(Request $request)
  {
    $query = House::query()->with('residents');
    $this->applySearch($query, $request, ['house_number']);
    $this->applySorting($query, $request, 'house_number');
    return $query;
  }

  public function show(Request $request, $id): JsonResponse
  {
    try {
      $propertyType = $request->input('type');
      $property = $this->findProperty($propertyType, $id);

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

  private function findProperty(?string $propertyType, $id)
  {
    return match ($propertyType) {
      'apartment' => Apartment::with(['floor.building', 'residents'])->find($id),
      'house' => House::with('residents')->find($id),
      default => Apartment::with(['floor.building', 'residents'])->find($id)
        ?? House::with('residents')->find($id)
    };
  }
}
