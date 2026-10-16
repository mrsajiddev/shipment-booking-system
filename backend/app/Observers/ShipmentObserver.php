<?php

namespace App\Observers;

use App\Enums\ShipmentStatus;
use App\Events\ShipmentStatusChanged;
use App\Models\Shipment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Watches the Shipment model.
 * Because it reacts to the MODEL, it works no matter where the change comes from:
 * the API, the queue job, a seeder or tinker. So the history can never be missed.
 */
class ShipmentObserver
{
    /** Before INSERT: fill in values the user must never choose. */
    public function creating(Shipment $shipment): void
    {
        $shipment->tracking_number ??= $this->newTrackingNumber();
        $shipment->status ??= ShipmentStatus::Pending;
    }

    /** After INSERT. */
    public function created(Shipment $shipment): void
    {
        ShipmentStatusChanged::dispatch($shipment, null, $shipment->status, Auth::id());
    }

    /** After UPDATE: fire the event only if the status column really changed. */
    public function updated(Shipment $shipment): void
    {
        if ($shipment->wasChanged('status')) {
            ShipmentStatusChanged::dispatch(
                $shipment,
                $shipment->getOriginal('status'),   // old value (still available inside 'updated')
                $shipment->status,
                Auth::id(),                         // null when the queue job made the change
            );
        }
    }

    private function newTrackingNumber(): string
    {
        do {
            $number = 'SHP'.now()->format('ymd').Str::upper(Str::random(6));
        } while (Shipment::where('tracking_number', $number)->exists());

        return $number;
    }
}
