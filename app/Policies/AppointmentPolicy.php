<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AppointmentPolicy extends SalonScopedPolicy
{
    public function update(User $user, Appointment $appointment): Response
    {
        return $this->ownsSalon($user, $appointment->salon_id);
    }
}
