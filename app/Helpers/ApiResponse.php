<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Standardized API Response Helper
 * 
 * This class enforces the GLOBAL API RESPONSE CONTRACT for the entire ERP system.
 * ALL API endpoints MUST use this helper to ensure consistent response format.
 * 
 * Based on the Class List API response structure, which is the standard format.
 * 
 * Response Structure:
 * {
 *   "success": true|false,
 *   "message": "Human readable message",
 *   "result": data|object|array,
 *   "error": "error details (only in debug mode)"
 * }
 * 
 * For paginated responses:
 * {
 *   "success": true,
 *   "message": "...",
 *   "result": {
 *     "data": [...],
 *     "current_page": 1,
 *     "last_page": 5,
 *     "per_page": 20,
 *     "total": 100,
 *     "from": 1,
 *     "to": 20
 *   }
 * }
 */
class ApiResponse
{
    /**
     * Success response with data
     * 
     * @param mixed $data The data to return
     * @param string $message Success message for toast
     * @param int $statusCode HTTP status code
     * @return JsonResponse
     */
    public static function success($data = null, string $message = 'Operation successful', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        // Add result only if data is provided
        if ($data !== null) {
            $response['result'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Success response for paginated data
     * 
     * @param LengthAwarePaginator $paginator
     * @param string $message
     * @param array|null $transformedData Override paginator items with transformed data
     * @return JsonResponse
     */
    public static function paginated(LengthAwarePaginator $paginator, string $message = 'Data fetched successfully', ?array $transformedData = null): JsonResponse
    {
        $items = $transformedData ?? $paginator->items();

        return response()->json([
            'success' => true,
            'message' => $message,
            'result' => [
                'data' => $items,
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ]
        ], 200);
    }

    /**
     * Success response for collection
     * 
     * @param Collection|array $collection
     * @param string $message
     * @return JsonResponse
     */
    public static function collection($collection, string $message = 'Data fetched successfully'): JsonResponse
    {
        return self::success($collection, $message);
    }

    /**
     * Created response (HTTP 201)
     * 
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    public static function created($data, string $message = 'Resource created successfully'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    /**
     * Updated response
     * 
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    public static function updated($data, string $message = 'Resource updated successfully'): JsonResponse
    {
        return self::success($data, $message, 200);
    }

    /**
     * Deleted response
     * 
     * @param string $message
     * @return JsonResponse
     */
    public static function deleted(string $message = 'Resource deleted successfully'): JsonResponse
    {
        return self::success(null, $message, 200);
    }

    /**
     * No content response (HTTP 204)
     * 
     * @return JsonResponse
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Error response
     * 
     * @param string $message Error message for toast
     * @param int $statusCode HTTP status code
     * @param mixed $errorDetails Additional error details (only shown in debug mode)
     * @param array $errors Validation errors array
     * @return JsonResponse
     */
    public static function error(string $message = 'An error occurred', int $statusCode = 500, $errorDetails = null, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        // Add error details only in debug mode
        if (config('app.debug') && $errorDetails !== null) {
            $response['error'] = $errorDetails;
        }

        // Add validation errors if present
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Validation error response (HTTP 422)
     * 
     * @param array|string $errors Validation errors
     * @param string $message Main error message
     * @return JsonResponse
     */
    public static function validationError($errors, string $message = 'Validation failed'): JsonResponse
    {
        // If errors is a string, convert to array
        if (is_string($errors)) {
            $errors = ['error' => [$errors]];
        }

        // Format errors for toast-friendly display
        $formattedErrors = self::formatValidationErrors($errors);

        return self::error($message, 422, null, $formattedErrors);
    }

    /**
     * Not found response (HTTP 404)
     * 
     * @param string $message
     * @return JsonResponse
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, 404);
    }

    /**
     * Unauthorized response (HTTP 401)
     * 
     * @param string $message
     * @return JsonResponse
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error($message, 401);
    }

    /**
     * Forbidden response (HTTP 403)
     * 
     * @param string $message
     * @return JsonResponse
     */
    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return self::error($message, 403);
    }

    /**
     * Server error response (HTTP 500)
     * 
     * @param string $message
     * @param mixed $errorDetails
     * @return JsonResponse
     */
    public static function serverError(string $message = 'Server error occurred', $errorDetails = null): JsonResponse
    {
        return self::error($message, 500, $errorDetails);
    }

    /**
     * Format validation errors for consistent display
     * 
     * @param array $errors
     * @return array
     */
    private static function formatValidationErrors(array $errors): array
    {
        $formatted = [];

        foreach ($errors as $field => $messages) {
            if (is_array($messages)) {
                // Take first error message for each field
                $formatted[$field] = $messages[0] ?? 'Invalid value';
            } else {
                $formatted[$field] = $messages;
            }
        }

        return $formatted;
    }

    /**
     * Get first validation error message for toast display
     * 
     * @param array $errors
     * @return string
     */
    public static function getFirstError(array $errors): string
    {
        foreach ($errors as $field => $messages) {
            if (is_array($messages) && !empty($messages)) {
                return $messages[0];
            } elseif (is_string($messages)) {
                return $messages;
            }
        }

        return 'Validation failed';
    }

    /**
     * Bulk operation response
     * 
     * @param array $created Successfully created items
     * @param array $failed Failed items with error details
     * @param string $resourceName Resource name (e.g., 'class', 'student')
     * @return JsonResponse
     */
    public static function bulkOperation(array $created, array $failed, string $resourceName = 'item'): JsonResponse
    {
        $successCount = count($created);
        $failedCount = count($failed);
        $total = $successCount + $failedCount;

        $message = sprintf(
            'Processed %d %s(s): %d successful, %d failed',
            $total,
            $resourceName,
            $successCount,
            $failedCount
        );

        return self::success([
            'created' => $created,
            'failed' => $failed,
            'total' => $total,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
        ], $message, $failedCount > 0 ? 207 : 201); // 207 = Multi-Status
    }

    /**
     * Custom response with full control
     * 
     * @param bool $success
     * @param string $message
     * @param mixed $result
     * @param int $statusCode
     * @param mixed $error
     * @return JsonResponse
     */
    public static function custom(bool $success, string $message, $result = null, int $statusCode = 200, $error = null): JsonResponse
    {
        $response = [
            'success' => $success,
            'message' => $message,
        ];

        if ($result !== null) {
            $response['result'] = $result;
        }

        if ($error !== null && config('app.debug')) {
            $response['error'] = $error;
        }

        return response()->json($response, $statusCode);
    }
}
