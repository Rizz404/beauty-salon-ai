<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ServicePolicy extends SalonScopedPolicy
{
    public function update(User $user, Service $service): Response
    {
        return $this->ownsSalon($user, $service->salon_id);
    }

    public function delete(User $user, Service $service): Response
    {
        return $this->ownsSalon($user, $service->salon_id);
    }
}
