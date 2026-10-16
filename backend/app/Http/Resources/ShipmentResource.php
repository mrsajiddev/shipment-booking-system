<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Controls exactly what JSON the Angular app receives.
 * Database columns can change without breaking the front end.
 */
class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tracking_number' => $this->tracking_number,
            'sender' => [
                'name' => $this->sender_name,
                'city' => $this->sender_city,
            ],
            'receiver' => [
                'name' => $this->receiver_name,
                'phone' => $this->receiver_phone,
                'address' => $this->receiver_address,
                'city' => $this->receiver_city,
            ],
            'weight_kg' => (float) $this->weight_kg,
            'price' => (float) $this->price,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'courier_reference' => $this->courier_reference,
            'booked_at' => $this->booked_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            // Lets Angular show or hide buttons without repeating the rules.
            'can' => [
                'cancel' => (bool) $request->user()?->can('cancel', $this->resource),
                'update_status' => (bool) $request->user()?->can('updateStatus', $this->resource),
            ],
            'history' => ShipmentHistoryResource::collection($this->whenLoaded('histories')),
        ];
    }
}
