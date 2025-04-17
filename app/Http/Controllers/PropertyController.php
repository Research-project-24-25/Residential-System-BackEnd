<?php

namespace App\Http\Controllers;

use App\Http\Requests\PropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Traits\ApiResponse;
use App\Traits\ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Throwable;

class PropertyController extends Controller
{
  use ApiResponse, ExceptionHandler;

  /**
   * List all properties with filtering, searching and sorting
   *
   * @param PropertyRequest $request
   * @return ResourceCollection|JsonResponse
   */
  public function index(PropertyRequest $request): ResourceCollection|JsonResponse
  {
    try {
      $query = Property::with('residents');

      // Apply type filter if specified
      if ($request->filled('property_type')) {
        $query->where('type', $request->input('property_type'));
      }

      // Apply all filters, sorting and pagination
      $properties = $query->filter($request)
        ->sort($request)
        ->paginate($request->input('per_page', 15));

      return PropertyResource::collection($properties);
    } catch (Throwable $e) {
      return $this->handleException($e);
    }
  }

  /**
   * Show a specific property
   *
   * @param string $id Property ID
   * @return JsonResponse
   */
  public function show($id): JsonResponse
  {
    try {
      $property = Property::with('residents')->find($id);

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
   * Store a newly created property
   *
   * @param PropertyRequest $request
   * @return JsonResponse
   */
  public function store(PropertyRequest $request): JsonResponse
  {
    try {
      $property = Property::create($request->validated());

      return $this->createdResponse(
        'Property created successfully',
        new PropertyResource($property)
      );
    } catch (Throwable $e) {
      return $this->handleException($e);
    }
  }

  /**
   * Update the specified property
   *
   * @param PropertyRequest $request
   * @param string $id Property ID
   * @return JsonResponse
   */
  public function update(PropertyRequest $request, $id): JsonResponse
  {
    try {
      $property = Property::find($id);

      if (!$property) {
        return $this->notFoundResponse('Property not found');
      }

      $property->update($request->validated());

      return $this->successResponse(
        'Property updated successfully',
        new PropertyResource($property)
      );
    } catch (Throwable $e) {
      return $this->handleException($e);
    }
  }

  /**
   * Remove the specified property
   *
   * @param string $id Property ID
   * @return JsonResponse
   */
  public function destroy($id): JsonResponse
  {
    try {
      $property = Property::find($id);

      if (!$property) {
        return $this->notFoundResponse('Property not found');
      }

      $property->delete();

      return $this->successResponse('Property deleted successfully');
    } catch (Throwable $e) {
      return $this->handleException($e);
    }
  }
}
