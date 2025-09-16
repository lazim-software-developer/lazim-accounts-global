<?php

namespace App\Core\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

trait LoggerTrait
{
    /**
     * Log request data before processing.
     *
     * @param Request $request
     */
    public function logRequest(Request $request)
    {
        // If the environment is 'development', return the exception message without logging
        if (app()->environment('development')) return;
        $logData = [
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->header('User-Agent'),
            'http_method'  => $request->method(),
            'url'          => $request->fullUrl(),
            "access_token" =>  $request->bearerToken(),
            'payload'      => $request->except(['password', 'password_confirmation']), // Exclude sensitive data
            'authenticated_user' => Auth::check() ? Auth::user()->id : 'Guest',
            'timestamp'    => now(),
        ];

        Log::channel('payload_logging')->info('Incoming Request:', $logData);
        // Log::info('Incoming Request:', $logData);
    }

    /**
     * Log response data after processing.
     *
     * @param Request $request
     * @param mixed $response
     */
    public function logResponse(Request $request, $response)
    {
        if (app()->environment('development')) return;
        $logData = [
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->header('User-Agent'),
            'http_method'  => $request->method(),
            'url'          => $request->fullUrl(),
            "access_token" =>  $request->bearerToken(),
            'response'     => is_array($response) ? $response : json_decode($response->getContent(), true),
            'status_code'  => method_exists($response, 'status') ? $response->status() : 200,
            'timestamp'    => now(),
        ];
        Log::channel('payload_logging')->info('Outgoing Response:', $logData);

        // Log::info('Outgoing Response:', $logData);
    }
}
