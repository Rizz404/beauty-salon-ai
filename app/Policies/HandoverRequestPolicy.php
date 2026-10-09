<?php

namespace App\Policies;

use App\Models\HandoverRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class HandoverRequestPolicy extends SalonScopedPolicy
{
    public function view(User $user, HandoverRequest $handover): Response
    {
        return $this->ownsSalon($user, $handover->conversation->salon_id);
    }

    public function update(User $user, HandoverRequest $handover): Response
    {
        return $this->ownsSalon($user, $handover->conversation->salon_id);
    }
}
