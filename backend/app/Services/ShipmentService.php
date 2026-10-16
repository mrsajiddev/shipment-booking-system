<?php

namespace App\Services;

use App\Enums\ShipmentStatus;
use App\Jobs\BookShipmentWithCourier;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * All shipment business rules live here, so controllers stay thin.
 *
 * Notice: this class is NOT registered anywhere. The container builds it
 * automatically (auto-wiring) because every constructor parameter is a class.
 * It sees RateCalculator, finds the singleton from the provider, and injects it.
 */
class ShipmentService
{
    public function __construct(private RateCalculator $rates)
    {
    }

    public function quote(float $weightKg, string $fromCity, string $toCity): array
    {
        return $this->rates->quote($weightKg, $fromCity, $toCity);
    }

    public function create(User $user, array $data): Shipment
    {
        $quote = $this->rates->quote((float) $data['weight_kg'], $data['sender_city'], $data['receiver_city']);

        return DB::transaction(function () use ($user, $data, $quote) {
            // The observer adds the tracking number and fires the "created" event.
            $shipment = $user->shipments()->create([...$data, 'price' => $quote['total']]);

            // Booking with the courier is slow, so it runs in the background.
            // afterCommit(): only send the job once the row is really saved.
            BookShipmentWithCourier::dispatch($shipment)->afterCommit();

            return $shipment;
        });
    }

    public function changeStatus(Shipment $shipment, ShipmentStatus $to): Shipment
    {
        if (! $shipment->status->canMoveTo($to)) {
            throw ValidationException::withMessages([
                'status' => "A shipment that is '{$shipment->status->label()}' cannot be changed to '{$to->label()}'.",
            ]);
        }

        $shipment->status = $to;

        if ($to === ShipmentStatus::Booked) {
            $shipment->booked_at = now();
        }

        // save() triggers ShipmentObserver::updated(), which fires ShipmentStatusChanged.
        $shipment->save();

        return $shipment;
    }

    public function cancel(Shipment $shipment): Shipment
    {
        return $this->changeStatus($shipment, ShipmentStatus::Cancelled);
    }
}
