<?php

namespace App\Jobs;

use App\Contracts\CourierGateway;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Services\ShipmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Background job: books the parcel with the courier company.
 * Runs on the 'courier' queue:  php artisan queue:work --queue=courier,notifications,default
 */
class BookShipmentWithCourier implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;      // try 3 times in total
    public int $timeout = 30;   // kill the job if it runs longer than 30 seconds

    public function __construct(public Shipment $shipment)
    {
        $this->onQueue('courier');
    }

    /** Wait 10s, then 30s, then 60s between retries. */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    /** Never book the same shipment in two workers at the same time. */
    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->shipment->id))->releaseAfter(10)];
    }

    /**
     * handle() also gets DEPENDENCY INJECTION from the container.
     * CourierGateway is an interface — the provider decides which class we get.
     */
    public function handle(CourierGateway $courier, ShipmentService $shipments): void
    {
        $shipment = $this->shipment->fresh();

        // IDEMPOTENT: if it was already booked or cancelled, do nothing.
        // A job may run twice, so always check first.
        if ($shipment === null || $shipment->status !== ShipmentStatus::Pending) {
            return;
        }

        $shipment->courier_reference = $courier->book($shipment);   // may throw -> retried

        $shipments->changeStatus($shipment, ShipmentStatus::Booked);
    }

    /** Called after the last retry fails. */
    public function failed(?Throwable $exception): void
    {
        Log::error('Courier booking failed after all retries', [
            'shipment_id' => $this->shipment->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
