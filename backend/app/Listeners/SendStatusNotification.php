<?php

namespace App\Listeners;

use App\Events\ShipmentStatusChanged;
use App\Notifications\ShipmentStatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\InteractsWithQueue;

/**
 * QUEUED listener: sending email is slow, so it runs in the background.
 * ShouldQueueAfterCommit = only queue it after the DB transaction is saved.
 */
class SendStatusNotification implements ShouldQueueAfterCommit
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function viaQueue(): string
    {
        return 'notifications';
    }

    public function handle(ShipmentStatusChanged $event): void
    {
        $event->shipment->user->notify(new ShipmentStatusUpdated($event->shipment, $event->to));
    }
}
