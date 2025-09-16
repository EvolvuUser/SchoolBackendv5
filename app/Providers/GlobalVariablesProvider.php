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
         app()->singleton('global_variables', function () {
            if (auth()->check()) { // Only load after JWT login
                $globalData = imagesforall();
                return [
                    'parent_app_url' => $globalData[0]['url'],
                    'codeigniter_app_url' => $globalData[0]['project_url'],
                ];
            }

            // Return empty or default values if not logged in
            return [
                'parent_app_url' => null,
                'codeigniter_app_url' => null,
            ];
        });
    }
}
