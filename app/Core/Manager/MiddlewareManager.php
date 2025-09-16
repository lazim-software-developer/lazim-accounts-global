<?php

namespace App\Core\Manager;

use App\Core\Middlewares\LogMiddleware;

class MiddlewareManager
{
    /**
     * Get the global middlewares.
     *
     * @return array
     */
    public static function getGlobalMiddlewares(): array
    {
        // custom middlewares api
        return [
            LogMiddleware::class,
            //SanctumMiddleware::class,
            // Add more middlewares here as needed
        ];
    }

    // public static array $middlewares = [
    //     LogMiddleware::class,
    //     // Other middlewares...
    // ];

    /**
     * Get the route-specific middlewares.
     *
     * @return array
     */
    public static function getRouteMiddlewares(): array
    {
        // custom middlewares api
        return [
            'auth.sanctum-custom' => \App\Core\Middlewares\SanctumMiddleware::class,
            // Add other route middlewares here
        ];
    }
}
