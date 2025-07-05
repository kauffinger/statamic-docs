<?php

namespace App\Providers;

use App\Search\CustomSearchProvider;
use Illuminate\Support\ServiceProvider;
use Statamic\Facades\Search;

class CustomSearchServiceProvider extends ServiceProvider
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
    public function boot(): void
    {
        // We need to register after the original provider is registered
        // Use a deferred approach to ensure our provider overrides the original
        $this->app->booted(function () {
            CustomSearchProvider::register();
        });
    }
}