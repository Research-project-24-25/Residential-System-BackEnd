<?php

namespace App\Traits;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
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

    // Add handling for authentication exceptions
    if ($exception instanceof AuthenticationException) {
      return $this->unauthorizedResponse($exception->getMessage());
    }

    // Add handling for method not allowed exceptions
    if ($exception instanceof MethodNotAllowedHttpException) {
      return $this->errorResponse('Method not allowed', 405);
    }

    // Add handling for database query exceptions
    if ($exception instanceof QueryException) {
      // In a production environment, you might want to log this and return a generic error.
      // For development/debugging, you might want to return the specific error.
      if (config('app.debug')) {
        return $this->errorResponse('Database Error: ' . $exception->getMessage(), 500);
      } else {
        return $this->errorResponse('An internal server error occurred', 500);
      }
    }

    // Generic error handling for other exceptions
    // Include more details in debug mode
    if (config('app.debug')) {
      return $this->errorResponse(
        'An unexpected error occurred: ' . get_class($exception) . ': ' . $exception->getMessage(),
        500,
        ['trace' => $exception->getTrace()]
      );
    } else {
      return $this->errorResponse('An internal server error occurred', 500);
    }
  }
}
