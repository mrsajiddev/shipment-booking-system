<?php

namespace App\Http\Controllers\Api;

use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteShipmentRequest;
use App\Http\Requests\StoreShipmentRequest;
use App\Http\Resources\ShipmentResource;
use App\Models\Shipment;
use App\Services\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ShipmentController extends Controller
{
    /** CONSTRUCTOR INJECTION: the container builds ShipmentService (and its RateCalculator). */
    public function __construct(private ShipmentService $shipments)
    {
    }

    /** GET /api/shipments?status=pending&search=SHP&per_page=10 */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Shipment::class);

        // Inline validation (the other style is a Form Request class).
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(ShipmentStatus::class)],
            'search' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $shipments = Shipment::query()
            ->visibleTo($request->user())
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['search'] ?? null, fn ($q, $term) => $q->where(function ($q) use ($term) {
                $q->where('tracking_number', 'like', "{$term}%")
                    ->orWhere('receiver_name', 'like', "%{$term}%");
            }))
            ->latest()
            ->paginate($filters['per_page'] ?? 10);

        return ShipmentResource::collection($shipments);
    }

    /** POST /api/shipments/quote — live price for the Angular form */
    public function quote(QuoteShipmentRequest $request): JsonResponse
    {
        $data = $request->validated();

        return response()->json([
            'data' => $this->shipments->quote((float) $data['weight_kg'], $data['sender_city'], $data['receiver_city']),
        ]);
    }

    /** POST /api/shipments */
    public function store(StoreShipmentRequest $request): JsonResponse
    {
        $shipment = $this->shipments->create($request->user(), $request->validated());

        return (new ShipmentResource($shipment))->response()->setStatusCode(201);
    }

    /** GET /api/shipments/{shipment} — route model binding finds the record or returns 404 */
    public function show(Shipment $shipment): ShipmentResource
    {
        Gate::authorize('view', $shipment);   // policy check inside the controller

        return new ShipmentResource($shipment->load('histories.changedBy'));
    }

    /** POST /api/shipments/{shipment}/cancel — the policy check is on the route (->can()) */
    public function cancel(Shipment $shipment): ShipmentResource
    {
        $this->shipments->cancel($shipment);

        return new ShipmentResource($shipment->load('histories.changedBy'));
    }
}
