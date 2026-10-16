<?php

namespace App\Http\Controllers\Api;

use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** GET /api/dashboard — counts per status for the Angular dashboard cards. */
class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        // One GROUP BY query instead of five COUNT queries.
        $counts = Shipment::query()
            ->visibleTo($request->user())
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $byStatus = collect(ShipmentStatus::cases())
            ->mapWithKeys(fn (ShipmentStatus $s) => [$s->value => (int) ($counts[$s->value] ?? 0)]);

        return response()->json([
            'data' => [
                'total' => $byStatus->sum(),
                'by_status' => $byStatus,
            ],
        ]);
    }
}
