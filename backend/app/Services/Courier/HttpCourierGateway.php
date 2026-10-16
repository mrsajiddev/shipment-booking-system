<?php

namespace App\Services\Courier;

use App\Contracts\CourierGateway;
use App\Exceptions\CourierUnavailableException;
use App\Models\Shipment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;

/**
 * Example of a real courier integration (TCS, Leopards, etc. would look similar).
 * Switch to it with COURIER_DRIVER=http. No other code needs to change.
 */
class HttpCourierGateway implements CourierGateway
{
    public function __construct(
        private HttpFactory $http,
        private string $baseUrl,
        private string $apiKey,
    ) {
    }

    public function book(Shipment $shipment): string
    {
        try {
            $response = $this->http
                ->baseUrl($this->baseUrl)
                ->withToken($this->apiKey)
                ->acceptJson()
                ->timeout(10)                    // never wait forever inside a job
                ->post('/bookings', [
                    'our_reference' => $shipment->tracking_number,
                    'consignee_name' => $shipment->receiver_name,
                    'consignee_phone' => $shipment->receiver_phone,
                    'consignee_address' => $shipment->receiver_address,
                    'destination_city' => $shipment->receiver_city,
                    'weight' => (float) $shipment->weight_kg,
                ])
                ->throw();
        } catch (ConnectionException|RequestException $e) {
            throw new CourierUnavailableException($e->getMessage(), previous: $e);
        }

        return (string) $response->json('reference');
    }
}
