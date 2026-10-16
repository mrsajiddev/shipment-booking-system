<?php

namespace App\Events;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * "Something happened": a shipment moved to a new status.
 * The event does no work itself. Listeners decide what to do about it.
 */
class ShipmentStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Shipment $shipment,
        public ?ShipmentStatus $from,
        public ShipmentStatus $to,
        public ?int $changedBy = null,
    ) {
    }
}
