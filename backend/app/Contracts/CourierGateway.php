<?php

namespace App\Contracts;

use App\Exceptions\CourierUnavailableException;
use App\Models\Shipment;

/**
 * ABSTRACTION: the rest of the app only knows WHAT a courier can do
 * ("book this parcel"), never HOW (which company, which API, which keys).
 *
 * The service provider decides which real class is used.
 */
interface CourierGateway
{
    /**
     * Book the parcel with the courier company and return their reference number.
     *
     * @throws CourierUnavailableException
     */
    public function book(Shipment $shipment): string;
}
