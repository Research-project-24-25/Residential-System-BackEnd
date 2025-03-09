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
      return $this->notFoundResponse();
    }

    if ($exception instanceof AuthorizationException) {
      return $this->forbiddenResponse($exception->getMessage());
    }

    // Log unexpected exceptions
    if (app()->environment('production')) {
      logger()->error($exception);
      return $this->errorResponse('An unexpected error occurred', 500);
    }

    // In non-production environments, return the exception details
    return $this->errorResponse($exception->getMessage(), 500);
  }
}
