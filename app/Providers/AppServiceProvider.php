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

        // Optimasi Performa SQLite di RAM (In-Memory Mapping & Cache)
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Database\Events\ConnectionEstablished::class,
            function (\Illuminate\Database\Events\ConnectionEstablished $event) {
                if ($event->connection->getDriverName() === 'sqlite') {
                    try {
                        $pdo = $event->connection->getPdo();
                        $pdo->exec('PRAGMA temp_store = MEMORY;');
                        $pdo->exec('PRAGMA mmap_size = 134217728;');
                        $pdo->exec('PRAGMA cache_size = -20000;');
                    } catch (\Throwable $e) {
                        // Abaikan jika tidak didukung pada environment tertentu
                    }
                }
            }
        );
    }
}
