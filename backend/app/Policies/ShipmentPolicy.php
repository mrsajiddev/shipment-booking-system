<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Answers "is THIS user allowed to do THIS action on THIS shipment?".
 * Used by Gate::authorize(), $user->can(), the 'can' route middleware and FormRequest::authorize().
 */
class ShipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // the list is filtered by Shipment::visibleTo()
    }

    public function view(User $user, Shipment $shipment): bool
    {
        return $user->isAdmin() || $shipment->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /** Returns a Response so the 403 message explains WHY. */
    public function cancel(User $user, Shipment $shipment): Response
    {
        if (! $this->view($user, $shipment)) {
            return Response::deny('This shipment does not belong to you.');
        }

        return $shipment->status->isCancellable()
            ? Response::allow()
            : Response::deny('Only pending or booked shipments can be cancelled.');
    }

    public function updateStatus(User $user, Shipment $shipment): bool
    {
        return $user->isAdmin();
    }
}
