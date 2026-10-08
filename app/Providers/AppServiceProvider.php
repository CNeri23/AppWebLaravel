<?php

namespace App\Providers;

use App\Auth\PersonaUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('persona', function ($app, array $config) {
            return new PersonaUserProvider($app['hash'], $config['model']);
        });
    }
}
