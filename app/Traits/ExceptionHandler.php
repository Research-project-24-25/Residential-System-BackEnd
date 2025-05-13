<?php

namespace App\Traits;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

trait ExceptionHandler
{
  /**
   * Handle common API exceptions.
   *
   * @param Throwable $exception
   * @return \Illuminate\Http\JsonResponse
   */
  protected function handleException(Throwable $exception)
  {
    if ($exception instanceof ValidationException) {
      return $this->validationErrorResponse($exception->errors());
    }

    if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
      return $this->notFoundResponse($exception->getMessage());
    }

    if ($exception instanceof AuthorizationException) {
      return $this->forbiddenResponse($exception->getMessage());
    }

    return $this->errorResponse($exception->getMessage());
  }
}
