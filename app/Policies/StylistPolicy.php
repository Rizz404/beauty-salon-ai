<?php

namespace App\Policies;

use App\Models\Stylist;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StylistPolicy extends SalonScopedPolicy
{
    public function update(User $user, Stylist $stylist): Response
    {
        return $this->ownsSalon($user, $stylist->salon_id);
    }

    public function delete(User $user, Stylist $stylist): Response
    {
        return $this->ownsSalon($user, $stylist->salon_id);
    }
}
