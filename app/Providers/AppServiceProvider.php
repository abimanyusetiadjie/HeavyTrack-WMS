<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\DeliveryOrder;
use App\Observers\DeliveryOrderObserver;

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
        DeliveryOrder::observe(DeliveryOrderObserver::class);
        \App\Models\Invoice::observe(\App\Observers\InvoiceObserver::class);
    }
}
