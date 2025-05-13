<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

trait ResourceHelpers
{
  /**
   * Safely handle a potential trashed relation when loading in a resource.
   * 
   * @param Model|null $relation The related model
   * @param callable $transformCallback Function to transform the relation data
   * @return array|null
   */
  protected function handleRelation($relation, callable $transformCallback)
  {
    // If no relation is loaded or it's null
    if (!$relation) {
      return null;
    }

    // Check if the model uses SoftDeletes and is trashed
    if (in_array(SoftDeletes::class, class_uses_recursive($relation)) && method_exists($relation, 'trashed')) {
      if ($relation->trashed()) {
        // Include a property to indicate this relation is deleted
        $data = $transformCallback($relation);
        if (is_array($data)) {
          $data['deleted_at'] = $relation->deleted_at;
          $data['is_deleted'] = true;
        }
        return $data;
      }
    }

    return $transformCallback($relation);
  }
}
