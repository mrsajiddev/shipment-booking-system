<?php

namespace Tests\Feature;

use App\Enums\ShipmentStatus;
use App\Jobs\BookShipmentWithCourier;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShipmentApiTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'sender_name' => 'Sajid',
            'sender_city' => 'Lahore',
            'receiver_name' => 'Ahmed',
            'receiver_phone' => '03001234567',
            'receiver_address' => 'House 1, Street 2',
            'receiver_city' => 'Karachi',
            'weight_kg' => 2,
        ], $overrides);
    }

    public function test_guest_cannot_list_shipments(): void
    {
        $this->getJson('/api/shipments')->assertUnauthorized();
    }

    public function test_customer_can_create_a_shipment_and_the_job_is_queued(): void
    {
        Queue::fake();
        Sanctum::actingAs($user = User::factory()->create());

        $response = $this->postJson('/api/shipments', $this->payload());

        // (200 base + 2kg * 150) * 1.5 out-of-city = 750
        $response->assertCreated()
            ->assertJsonPath('data.status.value', 'pending')
            ->assertJsonPath('data.price', 750.0);

        $shipment = Shipment::first();
        $this->assertNotNull($shipment->tracking_number);              // set by the observer
        $this->assertSame($user->id, $shipment->user_id);
        $this->assertDatabaseHas('shipment_status_histories', [        // written by the listener
            'shipment_id' => $shipment->id, 'to_status' => 'pending',
        ]);
        Queue::assertPushed(BookShipmentWithCourier::class);
    }

    public function test_validation_errors_are_returned_as_json(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/shipments', $this->payload(['receiver_phone' => '123', 'weight_kg' => 99]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['receiver_phone', 'weight_kg']);
    }

    public function test_customer_cannot_view_someone_elses_shipment(): void
    {
        Queue::fake();
        $shipment = Shipment::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/shipments/{$shipment->id}")->assertForbidden();
    }

    public function test_customer_cannot_use_admin_routes(): void
    {
        Queue::fake();
        $shipment = Shipment::factory()->create();
        Sanctum::actingAs($shipment->user);

        $this->patchJson("/api/admin/shipments/{$shipment->id}/status", ['status' => 'booked'])
            ->assertForbidden();
    }

    public function test_admin_cannot_make_an_invalid_status_change(): void
    {
        Queue::fake();
        $shipment = Shipment::factory()->create();   // pending
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/shipments/{$shipment->id}/status", ['status' => 'delivered'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_the_job_books_the_shipment_with_the_courier(): void
    {
        Queue::fake();
        $shipment = Shipment::factory()->create();

        (new BookShipmentWithCourier($shipment))->handle(
            app(\App\Contracts\CourierGateway::class),
            app(\App\Services\ShipmentService::class),
        );

        $shipment->refresh();
        $this->assertSame(ShipmentStatus::Booked, $shipment->status);
        $this->assertStringStartsWith('FAKE-', $shipment->courier_reference);
        $this->assertCount(2, $shipment->histories);   // pending -> booked
    }
}
