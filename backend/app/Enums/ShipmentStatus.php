<?php

namespace App\Enums;

/**
 * The life of a parcel:  pending -> booked -> in_transit -> delivered
 *                        (pending or booked can also become cancelled)
 *
 * Keeping the rules here means the controller, the job and the policy
 * all ask the same place "is this move allowed?".
 */
enum ShipmentStatus: string
{
    case Pending = 'pending';
    case Booked = 'booked';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    /** @return array<int, self> */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Pending => [self::Booked, self::Cancelled],
            self::Booked => [self::InTransit, self::Cancelled],
            self::InTransit => [self::Delivered],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canMoveTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }

    public function isCancellable(): bool
    {
        return $this->canMoveTo(self::Cancelled);
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Booked => 'Booked with courier',
            self::InTransit => 'In transit',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }
}
