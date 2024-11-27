<?php

namespace Shahil\MSGraph\Providers;

use Illuminate\Support\ServiceProvider;
use Shahil\MSGraph\Services\MSGraphClient;

class MSGraphServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Bind the service class
        $this->app->singleton('msgraph', function () {
            return new MSGraphClient();
        });

        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/msgraph.php',
            'msgraph'
        );
    }

    public function boot()
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/msgraph.php' => config_path('msgraph.php'),
        ], 'msgraph-config'); // Use 'msgraph-config' to match the command tag
    }
}
