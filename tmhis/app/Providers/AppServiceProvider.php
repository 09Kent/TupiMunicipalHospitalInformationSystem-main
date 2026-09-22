<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        $isProductionOrServerless = app()->environment('production') 
            || isset($_ENV['VERCEL']) 
            || isset($_SERVER['VERCEL']) 
            || str_contains(request()->getHost(), 'vercel.app') 
            || str_contains(request()->getHost(), 'onrender.com') 
            || request()->header('x-forwarded-proto') === 'https';

        if ($isProductionOrServerless && !app()->runningInConsole()) {
            URL::forceScheme('https');
        }
    }
}
