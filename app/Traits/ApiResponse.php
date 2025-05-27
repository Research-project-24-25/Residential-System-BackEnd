<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponse
{
  protected function successResponse(string $message, $data = null, int $status = 200): JsonResponse
  {
    $response = ['message' => $message];

    if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
      $response['data'] = $data;
    } elseif ($data !== null) {
      $response['data'] = $data;
    }

    return response()->json($response, $status);
  }

  protected function errorResponse(string $message, int $status = 400, array $additional = []): JsonResponse
  {
    return response()->json(['message' => $message], $status, $additional);
  }

  protected function createdResponse(string $message, $data = null): JsonResponse
  {
    return $this->successResponse($message, $data, 201);
  }

  protected function noContentResponse(): JsonResponse
  {
    return response()->json(null, 204);
  }

  protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
  {
    return $this->errorResponse($message, 401);
  }

  protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
  {
    return $this->errorResponse($message, 403);
  }

  protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
  {
    return $this->errorResponse($message, 404);
  }

  protected function validationErrorResponse(array $errors): JsonResponse
  {
    return response()->json([
      'message' => 'The given data was invalid.',
      'errors' => $errors
    ], 422);
  }
}
