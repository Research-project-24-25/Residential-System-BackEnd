<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Throwable;

trait SoftDeleteActions
{
  /**
   * Restore a soft-deleted model.
   *
   * @param string $modelClass The fully qualified class name of the model
   * @param int $id The ID of the model to restore
   * @return JsonResponse
   */
  protected function restoreModel(string $modelClass, int $id): JsonResponse
  {
    try {
      // Find the model including soft deleted ones
      $model = $modelClass::withTrashed()->findOrFail($id);

      // If model is not deleted, return error
      if (!$model->trashed()) {
        return $this->errorResponse('Record is not deleted', 422);
      }

      // Restore the model
      $model->restore();

      // Get model name for the response message
      $modelName = class_basename($modelClass);

      return $this->successResponse("{$modelName} restored successfully");
    } catch (Throwable $e) {
      return $this->handleException($e);
    }
  }

  /**
   * Permanently delete a soft-deleted model.
   *
   * @param string $modelClass The fully qualified class name of the model
   * @param int $id The ID of the model to permanently delete
   * @return JsonResponse
   */
  protected function forceDeleteModel(string $modelClass, int $id): JsonResponse
  {
    try {
      // Find the model including soft deleted ones
      $model = $modelClass::withTrashed()->findOrFail($id);

      // If model is not deleted, return error
      if (!$model->trashed()) {
        return $this->errorResponse('Record must be deleted first before force deleting', 422);
      }

      // Force delete the model
      $model->forceDelete();

      // Get model name for the response message
      $modelName = class_basename($modelClass);

      return $this->successResponse("{$modelName} permanently deleted");
    } catch (Throwable $e) {
      return $this->handleException($e);
    }
  }

  /**
   * Get a list of trashed (soft-deleted) models.
   *
   * @param string $modelClass The fully qualified class name of the model
   * @param callable|null $queryCallback Optional callback to modify the query
   * @param int $perPage Pagination count
   * @return JsonResponse
   */
  protected function getTrashedModels(string $modelClass, ?callable $queryCallback = null, int $perPage = 10): JsonResponse
  {
    try {
      // Start with a base query to get only trashed records
      $query = $modelClass::onlyTrashed();

      // If a callback was provided, apply it to the query
      if ($queryCallback !== null) {
        $queryCallback($query);
      }

      // Paginate the results
      $trashedModels = $query->paginate($perPage);

      // Get model name for the response message
      $modelName = class_basename($modelClass);

      return $this->successResponse(
        "Trashed {$modelName} records retrieved successfully",
        $trashedModels
      );
    } catch (Throwable $e) {
      return $this->handleException($e);
    }
  }
}
