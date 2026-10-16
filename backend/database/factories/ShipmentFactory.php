<?php

namespace Database\Factories;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    public function definition(): array
    {
        $cities = ['Lahore', 'Karachi', 'Islamabad', 'Faisalabad', 'Multan', 'Peshawar'];

        return [
            'user_id' => User::factory(),
            'sender_name' => fake()->name(),
            'sender_city' => 'Lahore',
            'receiver_name' => fake()->name(),
            'receiver_phone' => '03'.fake()->numerify('#########'),
            'receiver_address' => fake()->streetAddress(),
            'receiver_city' => fake()->randomElement($cities),
            'weight_kg' => fake()->randomFloat(2, 0.5, 20),
            'price' => fake()->randomFloat(2, 300, 4000),
        ];
    }

    public function status(ShipmentStatus $status): static
    {
        // Factories ignore $fillable, so status can be set directly here.
        $booked = ! in_array($status, [ShipmentStatus::Pending, ShipmentStatus::Cancelled], true);

        return $this->state(fn () => [
            'status' => $status,
            'courier_reference' => $booked ? 'FAKE-'.strtoupper(fake()->bothify('????####')) : null,
            'booked_at' => $booked ? now()->subDays(fake()->numberBetween(1, 5)) : null,
        ]);
    }
}
