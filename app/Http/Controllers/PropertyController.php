<?php

namespace App\Http\Controllers;

use App\Http\Requests\PropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Traits\HandlesFileUploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Throwable;

class PropertyController extends Controller
{
  use HandlesFileUploads;

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
        $validated['images'] = $this->handlePropertyImages($request->file('images'));
      } else {
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
        // Get old images for deletion
        $oldImages = json_decode($property->getRawOriginal('images'), true) ?? [];

        // Replace images (delete old, upload new)
        $validated['images'] = $this->replacePropertyImages($request->file('images'), $oldImages);
      } elseif (isset($validated['images']) && $validated['images'] === null) {
        // If images is explicitly set to null, remove existing images
        $oldImages = json_decode($property->getRawOriginal('images'), true) ?? [];
        $this->removePropertyImages($oldImages);
        $validated['images'] = [];
      } elseif (!isset($validated['images'])) {
        // If images field is not present in the request, don't update it
        unset($validated['images']);
      }

      $oldStatus = $property->status; // Get old status

      // Check if status is being updated and handle acquisition_date based on transitions
      if (isset($validated['status'])) {
        $newStatus = $validated['status'];

        if ($oldStatus === 'under_construction' && $newStatus === 'available_now') {
          $validated['acquisition_date'] = now();
        } elseif ($newStatus === 'under_construction') {
          $validated['acquisition_date'] = null;
        }
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
      $oldImages = json_decode($property->getRawOriginal('images'), true) ?? [];
      $this->removePropertyImages($oldImages);

      $property->delete();

      return $this->successResponse('Property deleted successfully');
    } catch (Throwable $e) {
      return $this->handleException($e);
    }
  }

  public function restore(int $id): JsonResponse
  {
    return $this->restoreModel(Property::class, $id);
  }

  public function trashed(Request $request): JsonResponse
  {
    return $this->getTrashedModels(Property::class);
  }

  public function forceDelete(int $id): JsonResponse
  {
    return $this->forceDeleteModel(Property::class, $id);
  }
}
