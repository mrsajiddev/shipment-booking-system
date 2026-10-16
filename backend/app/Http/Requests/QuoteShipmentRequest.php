<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuoteShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'weight_kg' => ['required', 'numeric', 'min:0.1', 'max:50'],
            'sender_city' => ['required', 'string', 'max:60'],
            'receiver_city' => ['required', 'string', 'max:60'],
        ];
    }
}
