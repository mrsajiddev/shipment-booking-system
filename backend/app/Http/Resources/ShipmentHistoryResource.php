<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'from' => $this->from_status?->value,
            'to' => $this->to_status->value,
            'to_label' => $this->to_status->label(),
            'changed_by' => $this->changedBy?->name ?? 'System',
            'at' => $this->created_at?->toIso8601String(),
        ];
    }
}
