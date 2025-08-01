<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\VisaApplication;
use App\Observers\VisaApplicationObserver;
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
        VisaApplication::observe(VisaApplicationObserver::class);
    }
}
