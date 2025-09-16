<?php

namespace App\Core\Middlewares;

use App\Core\Services\ResponseService;
use App\Core\Services\SanctumService;
use Closure;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class SanctumMiddleware
{
    protected $sanctumService;

    public function __construct(SanctumService $sanctumService)
    {
        $this->sanctumService = $sanctumService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     * @throws \Exception
     */
    public function handle(Request $request, Closure $next)
    {
        // Retrieve the access token from request header
        $accessToken = $request->bearerToken();

        if (!$accessToken) {
            return ResponseService::error([], 'Access token is required', 401);
            // return response()->json(['error' => 'Access token is required'], 401);
        }

        try {
            // Retrieve the user associated with the token
            $user = $this->sanctumService->getUserFromAccessToken($accessToken);

            if (!$user) {
                return ResponseService::error([], 'Invalid or expired access token', 401);
                //return response()->json(['error' => 'Invalid or expired access token'], 401);
            }

            // Attach the user to the request object for later use in controllers
            // $request->attributes->set('user', $user);
            // $request->merge(['authenticated_user' => $user]);
            $request->attributes->set('authenticated_user', $user);
        } catch (Exception $e) {
            throw new AuthenticationException($e->getMessage());
            //return ResponseService::error([],  $e->getMessage(), 401);
            //return response()->json(['error' => $e->getMessage()], 401);
        }

        return $next($request);
    }
}
