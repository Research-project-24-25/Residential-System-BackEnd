<?php

namespace App\Http\Controllers;

use App\Http\Requests\PropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Throwable;

class PropertyController extends Controller
{
  public function index(PropertyRequest $request): ResourceCollection|JsonResponse
  {
    try {
      $properties = Property::query()
        ->filter($request)
        ->sort($request)
        ->paginate($request->get('per_page', 10));

      return PropertyResource::collection($properties);
    } catch (Throwable $e) {
      return $this->handleException($e);
    }
  }

  public function show($id): JsonResponse
  {
    try {
      $property = Property::find($id);

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
