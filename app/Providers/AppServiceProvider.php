<?php

namespace App\Providers;

use App\Models\PermintaanAbsen;
use App\Observers\PermintaanAbsenObserver;
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
        PermintaanAbsen::observe(PermintaanAbsenObserver::class);
    }
}
