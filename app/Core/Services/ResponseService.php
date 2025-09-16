<?php

namespace App\Core\Services;


class ResponseService
{
    /**
     * Success Response
     *
     * @param mixed  $data
     * @param string $message
     * @param int    $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public static function success($data = null, $message = 'Success', $statusCode = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'errors'  => [],
        ], $statusCode);
    }

    /**
     * Error Response
     *
     * @param string|array $errors
     * @param string       $message
     * @param int          $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public static function error($errors = [], $message = 'Error', $statusCode = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => is_array($errors) ? $errors :  $errors //['error' => $errors],
        ], $statusCode);
    }

    public static function errorWithTrace($errors = [],  $message = 'Error', $statusCode = 400, $trace)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'trace' => $trace,
            'errors'  => is_array($errors) ? $errors :  $errors //['error' => $errors],
        ], $statusCode);
    }
    // public static function error($errors = [], $message = 'Error', $statusCode = 400)
    // {
    //     // Convert validation errors from array to key-value pairs with single string values
    //     if (is_array($errors)) {
    //         $formattedErrors = [];
    //         foreach ($errors as $key => $value) {
    //             $formattedErrors[$key] = is_array($value) ? $value[0] : $value;
    //         }
    //         $errors = $formattedErrors;
    //     }

    //     return response()->json([
    //         'success' => false,
    //         'message' => $message,
    //         'errors'  => $errors,
    //         'data'    => null,
    //     ], $statusCode);
    // }
}
