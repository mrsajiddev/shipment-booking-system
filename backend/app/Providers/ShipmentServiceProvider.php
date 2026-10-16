<?php

namespace App\Providers;

use App\Contracts\CourierGateway;
use App\Events\ShipmentStatusChanged;
use App\Listeners\RecordStatusHistory;
use App\Listeners\SendStatusNotification;
use App\Models\Shipment;
use App\Observers\ShipmentObserver;
use App\Policies\ShipmentPolicy;
use App\Services\Courier\FakeCourierGateway;
use App\Services\Courier\HttpCourierGateway;
use App\Services\RateCalculator;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ShipmentServiceProvider extends ServiceProvider
{
    /**
     * register(): ONLY put things into the container. Don't use other services here.
     */
    public function register(): void
    {
        // SINGLETON: built once per request/worker, the same object is reused.
        // Needed as a closure because the constructor takes plain floats.
        $this->app->singleton(RateCalculator::class, fn () => new RateCalculator(
            baseFee: (float) config('shipping.rates.base_fee'),
            perKg: (float) config('shipping.rates.per_kg'),
            outOfCityMultiplier: (float) config('shipping.rates.out_of_city_multiplier'),
        ));

        // INTERFACE -> CLASS binding. Anyone who asks for CourierGateway gets
        // the class chosen in config. Change COURIER_DRIVER, nothing else changes.
        $this->app->bind(CourierGateway::class, function (Application $app) {
            return match (config('shipping.courier.driver')) {
                'http' => new HttpCourierGateway(
                    $app->make(HttpFactory::class),      // the container builds this one too
                    (string) config('shipping.courier.base_url'),
                    (string) config('shipping.courier.api_key'),
                ),
                default => new FakeCourierGateway((float) config('shipping.courier.fail_rate')),
            };
        });

        // ShipmentService is not listed: the container auto-wires it.
    }

    /**
     * boot(): runs after ALL providers are registered, so it is safe to use
     * other services (events, gates, models).
     */
    public function boot(): void
    {
        Shipment::observe(ShipmentObserver::class);

        Gate::policy(Shipment::class, ShipmentPolicy::class);

        // Event discovery is turned off in bootstrap/app.php,
        // so the full event map is visible here in one place.
        Event::listen(ShipmentStatusChanged::class, RecordStatusHistory::class);
        Event::listen(ShipmentStatusChanged::class, SendStatusNotification::class);
    }
}
