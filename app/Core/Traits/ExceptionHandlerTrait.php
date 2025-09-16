<?php

namespace App\Core\Traits;

use App\Core\Services\ResponseService;
use Throwable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\JsonResponse;
use App\Services\ApiResponseService;
use Illuminate\Support\Facades\Log;

trait ExceptionHandlerTrait
{
    /**
     * Handle exceptions and return a structured response
     *
     * @param Throwable $exception
     * @return JsonResponse
     */
    public function handleException(Throwable $exception): JsonResponse
    {

        // If the environment is 'development', return the exception message without logging
        if (app()->environment('development')) {
            return ResponseService::errorWithTrace($exception->getMessage(), 'Exception', 500, $exception->getTraceAsString());
        }

        // // Check if we should show the exception details based on the environment variable
        // if (env('SHOW_EXCEPTION_DETAILS', false)) {
        //     return ResponseService::error($exception->getMessage(), 'Exception', 500);
        // }

        // Log the exception for debugging
        Log::channel('exception_error_logging')->error($exception);
        //Log::error($exception);

        // Handle different types of exceptions
        if ($exception instanceof ModelNotFoundException) {
            return ResponseService::error('Resource not found', 'Model Not Found', 404);
        }

        if ($exception instanceof NotFoundHttpException) {
            return ResponseService::error('API endpoint not found', 'Route Not Found', 404);
        }

        if ($exception instanceof ValidationException) {
            return ResponseService::error($exception->errors(), 'Validation Error', 422);
        }

        if ($exception instanceof AuthenticationException) {
            return ResponseService::error('Unauthenticated', 'Authentication Error', 401);
        }

        // Default response for other exceptions
        return ResponseService::error(
            'An unexpected error occurred. Please try again later.',
            'Server Error',
            500
        );
    }
}
