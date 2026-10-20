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

        // Force HTTPS on cloud (Railway) or when force_https is enabled, allowing HTTP on offline Windows LAN
        if (config('app.force_https') || isset($_SERVER['RAILWAY_ENVIRONMENT']) || str_starts_with((string) config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        DeliveryOrder::observe(DeliveryOrderObserver::class);
        \App\Models\Invoice::observe(\App\Observers\InvoiceObserver::class);
        \App\Models\GoodsReceipt::observe(\App\Observers\GoodsReceiptObserver::class);
    }
}
