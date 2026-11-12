<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // MODE=standalone means the service runs without optional external
        // integrations. This API has no optional external integrations; only
        // its configured database is required, so standalone mode needs no
        // stubs. The value is accepted and surfaced for operators.
        if (config('app.mode') === 'standalone') {
            // no-op: no optional dependencies to disable
        }
    }
}
