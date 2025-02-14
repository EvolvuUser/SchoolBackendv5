<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class GlobalVariablesProvider extends ServiceProvider
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
        $globalData = imagesforall();


        // You can also store it in the container, so it can be accessed globally via `app()` helper
        app()->singleton('global_variables', function () use ($globalData) {
            return [
                'parent_app_url' => $globalData[0]['url'],
                'codeigniter_app_url' => $globalData[0]['project_url'],
            ];
        });
    }
}
