<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Staff manage the records of their own salon and nothing else. A record that
 * belongs to another salon is answered with "not found", so its existence is
 * never revealed.
 */
abstract class SalonScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentSalon() !== null;
    }

    public function create(User $user): bool
    {
        return $user->currentSalon() !== null;
    }

    protected function ownsSalon(User $user, ?int $salonId): Response
    {
        $salon = $user->currentSalon();

        return $salon !== null && $salon->id === $salonId
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
