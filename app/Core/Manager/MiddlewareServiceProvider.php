<?php

namespace App\Core\Manager;

use App\Core\Manager\MiddlewareManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel;

class MiddlewareServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    // public function boot(Kernel $kernel): void
    // {
    //     // Register global middlewares
    //     foreach (MiddlewareManager::getGlobalMiddlewares() as $middleware) {
    //         // $kernel->pushMiddleware($middleware);
    //         // $kernel->prependMiddleware($middleware);

    //     }

    //     // Register route middlewares
    //     $router = $this->app['router'];
    //     foreach (MiddlewareManager::getRouteMiddlewares() as $key => $middleware) {
    //         $router->aliasMiddleware($key, $middleware);
    //     }
    // }

    public function boot()
    {
        $kernel = $this->app->make(Kernel::class);

        // Register global middlewares
        foreach (MiddlewareManager::getGlobalMiddlewares() as $middleware) {
            $kernel->pushMiddleware($middleware);
        }
        // $kernel->pushMiddleware(CustomMiddlewareOne::class);

        // Register route middlewares
        $router = $this->app['router'];
        foreach (MiddlewareManager::getRouteMiddlewares() as $key => $middleware) {
            $router->aliasMiddleware($key, $middleware);
        }
        // $router->aliasMiddleware('custom.one', CustomMiddlewareOne::class);
        // $router->aliasMiddleware('custom.two', CustomMiddlewareTwo::class);
    }
}
