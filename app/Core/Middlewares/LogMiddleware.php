<?php

namespace App\Core\Middlewares;

use Closure;
use App\Core\Traits\LoggerTrait;
use Illuminate\Http\Request;

class LogMiddleware
{
    use LoggerTrait;

    public function handle(Request $request, Closure $next)
    {
        if ($this->isApiRequest($request)) {
            $this->logRequest($request);
            $response = $next($request);
            $this->logResponse($request, $response);
            return $response;
        }

        return $next($request); // Ensure non-API requests proceed normally
    }

    private function isApiRequest(Request $request): bool
    {
        return $request->is('api/*'); // Adjust as needed
    }
}
