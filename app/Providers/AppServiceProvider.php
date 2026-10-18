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
        \Illuminate\Database\Eloquent\Model::unguard();
        // Force HTTPS on cloud (Railway) or when FORCE_HTTPS=true, allowing HTTP on offline Windows LAN
        if (env('FORCE_HTTPS', isset($_SERVER['RAILWAY_ENVIRONMENT']))) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        DeliveryOrder::observe(DeliveryOrderObserver::class);
        \App\Models\Invoice::observe(\App\Observers\InvoiceObserver::class);
        \App\Models\GoodsReceipt::observe(\App\Observers\GoodsReceiptObserver::class);
    }
}
