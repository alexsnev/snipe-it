<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleRequests\HandleRequests;

class LivewireServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * Livewire renders the update endpoint as url(getUpdateUri()). The stock
     * getUpdateUri() returns a host-relative path, which already contains the
     * path of APP_URL (forced as the root URL in AppServiceProvider), so url()
     * would add that path a second time on a subdirectory install. Return the
     * route's path relative to the application root instead, so url() adds the
     * APP_URL path exactly once.
     */
    public function register(): void
    {
        $this->app->extend(HandleRequests::class, fn () => new class extends HandleRequests
        {
            public function getUpdateUri(): string
            {
                $route = $this->updateRoute ?? $this->findUpdateRoute();

                return '/'.ltrim($route->uri(), '/');
            }
        });
    }

    /**
     * Bootstrap services.
     *
     * Routes are matched relative to the application root, so the APP_URL path
     * must not be part of them: Livewire's own endpoint path is used as-is, with
     * only an explicitly configured livewire.url_prefix in front of it.
     */
    public function boot(): void
    {
        $prefix = trim((string) config('livewire.url_prefix', ''), '/');
        $prefix = $prefix === '' ? '' : '/'.$prefix;

        Livewire::setUpdateRoute(function (array $handle, string $path) use ($prefix) {
            return Route::post($prefix.$path, $handle);
        });

        Livewire::setScriptRoute(function (array $handle, string $path) use ($prefix) {
            return Route::get($prefix.$path, $handle);
        });
    }
}
