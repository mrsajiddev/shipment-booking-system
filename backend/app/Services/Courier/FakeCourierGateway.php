<?php

namespace App\Services\Courier;

use App\Contracts\CourierGateway;
use App\Exceptions\CourierUnavailableException;
use App\Models\Shipment;
use Illuminate\Support\Str;

/**
 * Used in local development and tests. No real API call.
 * Set COURIER_FAIL_RATE=0.5 in .env to see queue retries in action.
 */
class FakeCourierGateway implements CourierGateway
{
    public function __construct(private float $failRate = 0.0)
    {
    }

    public function book(Shipment $shipment): string
    {
        if ($this->failRate > 0 && (mt_rand() / mt_getrandmax()) < $this->failRate) {
            throw new CourierUnavailableException('Fake courier is not responding (simulated failure).');
        }

        return 'FAKE-'.Str::upper(Str::random(8));
    }
}
