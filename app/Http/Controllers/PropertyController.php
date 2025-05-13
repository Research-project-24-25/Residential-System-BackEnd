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
      $validated = $request->validated();

      // Handle image uploads
      if ($request->hasFile('images')) {
        $images = $this->handleImageUploads($request->file('images'));
        $validated['images'] = $images;
      } else {
        // Initialize with empty array if no images are provided
        $validated['images'] = [];
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
        if (!empty($property->getRawOriginal('images'))) {
          $this->removeOldImages(json_decode($property->getRawOriginal('images'), true) ?? []);
        }

        $images = $this->handleImageUploads($request->file('images'));
        $validated['images'] = $images;
      } elseif (isset($validated['images']) && $validated['images'] === null) {
        // If images is explicitly set to null, remove existing images
        if (!empty($property->getRawOriginal('images'))) {
          $this->removeOldImages(json_decode($property->getRawOriginal('images'), true) ?? []);
        }
        $validated['images'] = [];
      } elseif (!isset($validated['images'])) {
        // If images field is not present in the request, don't update it
        unset($validated['images']);
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

  public function destroy($id): JsonResponse
  {
    try {
      $property = Property::findOrFail($id);

      // Remove property images
      if (!empty($property->getRawOriginal('images'))) {
        $this->removeOldImages(json_decode($property->getRawOriginal('images'), true) ?? []);
      }

      $property->delete();

      return $this->successResponse('Property deleted successfully');
    } catch (Throwable $e) {
      return $this->handleException($e);
    }
  }

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

  private function removeOldImages($images): void
  {
    if (!is_array($images)) {
      return;
    }

    foreach ($images as $image) {
      $path = public_path($image);
      if (file_exists($path)) {
        unlink($path);
      }
    }
  }
}
