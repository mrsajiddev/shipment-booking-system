<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateShipmentStatusRequest;
use App\Http\Resources\ShipmentResource;
use App\Models\Shipment;
use App\Services\ShipmentService;
use Illuminate\Support\Facades\Gate;

/**
 * PATCH /api/admin/shipments/{shipment}/status
 * Protected twice: 'role:admin' middleware on the route group, and the policy here.
 */
class ShipmentStatusController extends Controller
{
    /** METHOD INJECTION: Laravel injects ShipmentService straight into the method. */
    public function __invoke(UpdateShipmentStatusRequest $request, Shipment $shipment, ShipmentService $shipments): ShipmentResource
    {
        Gate::authorize('updateStatus', $shipment);

        $shipments->changeStatus($shipment, ShipmentStatus::from($request->validated('status')));

        return new ShipmentResource($shipment->load('histories.changedBy'));
    }
}
