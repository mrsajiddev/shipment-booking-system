<?php

namespace Database\Seeders;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Note: the default "WithoutModelEvents" trait is removed on purpose,
     * because ShipmentObserver must run to create tracking numbers and history.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);
        $admin->forceFill(['role' => 'admin'])->save();

        $customer = User::factory()->create([
            'name' => 'Ali Customer',
            'email' => 'customer@example.com',
        ]);

        $plan = [
            ShipmentStatus::Pending->value => 3,
            ShipmentStatus::Booked->value => 2,
            ShipmentStatus::InTransit->value => 2,
            ShipmentStatus::Delivered->value => 3,
            ShipmentStatus::Cancelled->value => 1,
        ];

        foreach ($plan as $status => $count) {
            Shipment::factory()
                ->count($count)
                ->for($customer)
                ->status(ShipmentStatus::from($status))
                ->create();
        }
    }
}
