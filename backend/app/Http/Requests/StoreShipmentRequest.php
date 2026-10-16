<?php

namespace App\Http\Requests;

use App\Models\Shipment;
use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentRequest extends FormRequest
{
    /** Authorization inside the form request: asks ShipmentPolicy::create(). */
    public function authorize(): bool
    {
        return $this->user()->can('create', Shipment::class);
    }

    public function rules(): array
    {
        return [
            'sender_name' => ['required', 'string', 'max:100'],
            'sender_city' => ['required', 'string', 'max:60'],
            'receiver_name' => ['required', 'string', 'max:100'],
            'receiver_phone' => ['required', 'string', 'regex:/^03\d{9}$/'],
            'receiver_address' => ['required', 'string', 'max:255'],
            'receiver_city' => ['required', 'string', 'max:60'],
            'weight_kg' => ['required', 'numeric', 'min:0.1', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'receiver_phone.regex' => 'Enter a valid mobile number like 03001234567.',
            'weight_kg.max' => 'We only accept parcels up to 50 kg.',
        ];
    }
}
