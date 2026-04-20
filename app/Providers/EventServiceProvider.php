<?php

namespace App\Providers;

use App\Events\OrderCreated;
use App\Events\SaleCreated;
use App\Events\StockTransferCreated;
use App\Events\KPIEvent;
use App\Events\AIOperationCompleted;
use App\Listeners\SendOrderNotification;
use App\Listeners\SendSaleNotification;
use App\Listeners\SendStockTransferNotification;
use App\Listeners\LogKPIEvent;
use App\Listeners\RecordOrderConversion;
use App\Listeners\RecordAIOperationMetrics;
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
            RecordOrderConversion::class,
        ],
        SaleCreated::class => [
            SendSaleNotification::class,
        ],
        StockTransferCreated::class => [
            SendStockTransferNotification::class,
        ],
        KPIEvent::class => [
            LogKPIEvent::class,
        ],
        AIOperationCompleted::class => [
            RecordAIOperationMetrics::class,
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
