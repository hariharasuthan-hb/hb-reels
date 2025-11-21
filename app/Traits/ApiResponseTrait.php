<?php

namespace App\Traits;

use App\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Trait for standardized API responses.
 * 
 * Provides consistent response formatting for all API endpoints.
 * 
 * Usage:
     *   return $this->successResponse('Message', $data);
     *   return $this->errorResponse('Error message', 400);
 *   return $this->paginatedResponse($paginator, 'Data retrieved successfully');
 */
trait ApiResponseTrait
{
    /**
     * Return a successful JSON response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function successResponse(string $message = 'Success', array $data = [], int $statusCode = 200): JsonResponse
    {
        return ApiResponse::success($message, $data, $statusCode);
    }

    /**
     * Return an error JSON response.
     *
     * @param string $message
     * @param int $statusCode
     * @param array $errors
     * @return JsonResponse
     */
    protected function errorResponse(string $message, int $statusCode = 400, array $errors = []): JsonResponse
    {
        return ApiResponse::error($message, $errors, $statusCode);
    }

    /**
     * Return a paginated JSON response.
     *
     * @param LengthAwarePaginator $paginator
     * @param string $message
     * @return JsonResponse
     */
    protected function paginatedResponse(LengthAwarePaginator $paginator, string $message = 'Data retrieved successfully'): JsonResponse
    {
        return ApiResponse::success($message, [
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    protected function tokenResponse(string $message, string $token, array $data = [], int $statusCode = 200): JsonResponse
    {
        return ApiResponse::token($message, $token, $data, $statusCode);
    }

    /**
     * Return a validation error response.
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    protected function validationErrorResponse(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors);
    }

    /**
     * Return an unauthorized response.
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Return a not found response.
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }
}

