<?php

namespace App\Http\Controllers;

use App\Http\Requests\PropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Throwable;

class PropertyController extends Controller
{
  /**
   * Get all properties with optional pagination
   * 
   * @param Request $request
   * @return ResourceCollection|JsonResponse
   */
  public function index(Request $request): ResourceCollection|JsonResponse
  {
    try {
      $perPage = $request->get('per_page', 10);

      $properties = Property::query()
        ->sort($request)
        ->paginate($perPage);

      return PropertyResource::collection($properties);
    } catch (Throwable $e) {
      return $this->handleException($e);
    }
  }

  /**
   * Get filtered properties
   * 
   * @param PropertyRequest $request
   * @return ResourceCollection|JsonResponse
   */
  public function filter(PropertyRequest $request): ResourceCollection|JsonResponse
  {
    try {
      $perPage = $request->get('per_page', 10);

      $properties = Property::query()
        ->filter($request)
        ->sort($request)
        ->paginate($perPage);

      return PropertyResource::collection($properties);
    } catch (Throwable $e) {
      return $this->handleException($e);
    }
  }

  /**
   * Get a single property
   * 
   * @param int $id
   * @return JsonResponse
   */
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

  /**
   * Create a new property
   * 
   * @param PropertyRequest $request
   * @return JsonResponse
   */
  public function store(PropertyRequest $request): JsonResponse
  {
    try {
      $validated = $request->validated();

      // Handle image uploads
      if ($request->hasFile('images')) {
        $images = $this->handleImageUploads($request->file('images'));
        $validated['images'] = $images;
      }

      $property = Property::create($validated);

      return $this->createdResponse(
        'Property created successfully',
        new PropertyResource($property)
      );
    } catch (Throwable $e) {
      return $this->handleException($e);
    }
  }

  /**
   * Update an existing property
   * 
   * @param PropertyRequest $request
   * @param int $id
   * @return JsonResponse
   */
  public function update(PropertyRequest $request, $id): JsonResponse
  {
    try {
      $property = Property::find($id);

      if (!$property) {
        return $this->notFoundResponse('Property not found');
      }

      $validated = $request->validated();

      // Handle image uploads
      if ($request->hasFile('images')) {
        // Remove old images if they exist
        if (!empty($property->images)) {
          $this->removeOldImages($property->images);
        }

        $images = $this->handleImageUploads($request->file('images'));
        $validated['images'] = $images;
      }

      $property->update($validated);

      return $this->successResponse(
        'Property updated successfully',
        new PropertyResource($property)
      );
    } catch (Throwable $e) {
      return $this->handleException($e);
    }
  }

  /**
   * Delete a property
   * 
   * @param int $id
   * @return JsonResponse
   */
  public function destroy($id): JsonResponse
  {
    try {
      $property = Property::find($id);

      if (!$property) {
        return $this->notFoundResponse('Property not found');
      }

      // Remove property images
      if (!empty($property->images)) {
        $this->removeOldImages($property->images);
      }

      $property->delete();

      return $this->successResponse('Property deleted successfully');
    } catch (Throwable $e) {
      return $this->handleException($e);
    }
  }

  /**
   * Handle uploading property images
   * 
   * @param array $images
   * @return array
   */
  private function handleImageUploads($images): array
  {
    $uploadedImages = [];

    foreach ($images as $image) {
      $filename = time() . '_' . $image->getClientOriginalName();
      // Store in public directory instead of storage
      $image->move(public_path('property-images'), $filename);
      $uploadedImages[] = 'property-images/' . $filename;
    }

    return $uploadedImages;
  }

  /**
   * Remove old property images
   * 
   * @param array $images
   * @return void
   */
  private function removeOldImages($images): void
  {
    foreach ($images as $image) {
      $path = public_path($image);
      if (file_exists($path)) {
        unlink($path);
      }
    }
  }
}
