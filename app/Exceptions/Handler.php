<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Helpers\ApiResponse;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // ============================================
        // STANDARDIZED API EXCEPTION HANDLING
        // All exceptions return ApiResponse format
        // for consistent toast notification display
        // ============================================

        // Validation Exception (HTTP 422)
        $this->renderable(function (ValidationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $errors = $e->errors();
                $firstError = ApiResponse::getFirstError($errors);
                
                return ApiResponse::validationError(
                    $errors,
                    $firstError // First error as main message for toast
                );
            }
        });

        // Authentication Exception (HTTP 401)
        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::unauthorized(
                    $e->getMessage() ?: 'Unauthenticated. Please login to continue.'
                );
            }
            
            // For web requests, use default behavior
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 401);
        });

        // Authorization Exception (HTTP 403)
        $this->renderable(function (AuthorizationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::forbidden(
                    $exception->getMessage() ?: 'You do not have permission to perform this action.'
                );
            }
        });

        // Model Not Found Exception (HTTP 404)
        $this->renderable(function (ModelNotFoundException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $modelName = class_basename($e->getModel());
                return ApiResponse::notFound(
                    "The requested {$modelName} was not found."
                );
            }
        });

        // Not Found HTTP Exception (HTTP 404)
        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::notFound(
                    'The requested resource was not found.'
                );
            }
            
            // For web requests, use default behavior
            return response()->json([
                'success' => false,
                'message' => 'Resource not found.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 404);
        });

        // HTTP Exception (any HTTP error)
        $this->renderable(function (HttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error(
                    $e->getMessage() ?: 'An error occurred',
                    $e->getStatusCode(),
                    config('app.debug') ? $e->getTrace() : null
                );
            }
        });

        // Generic exception (HTTP 500) - catch-all
        $this->renderable(function (Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::serverError(
                    config('app.debug') 
                        ? $e->getMessage() 
                        : 'An unexpected error occurred. Please try again later.',
                    config('app.debug') ? [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ] : null
                );
            }
        });
    }
}
