<?php

namespace App\Listeners;

use App\Events\ShipmentStatusChanged;

/**
 * SYNC listener: runs immediately, inside the same request (and the same DB transaction).
 * History must be saved together with the status change, so it is not queued.
 */
class RecordStatusHistory
{
    public function handle(ShipmentStatusChanged $event): void
    {
        $event->shipment->histories()->create([
            'from_status' => $event->from,
            'to_status' => $event->to,
            'changed_by' => $event->changedBy,
        ]);
    }
}
