<?php

namespace App\Providers;

use App\Events\OrderCreated;
use App\Events\SaleCreated;
use App\Events\StockTransferCreated;
use App\Listeners\SendOrderNotification;
use App\Listeners\SendSaleNotification;
use App\Listeners\SendStockTransferNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        OrderCreated::class => [
            SendOrderNotification::class,
        ],
        SaleCreated::class => [
            SendSaleNotification::class,
        ],
        StockTransferCreated::class => [
            SendStockTransferNotification::class,
        ],
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
