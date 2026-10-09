<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentSalon;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAppointmentStatusRequest;
use App\Models\Appointment;
use App\Modules\Appointment\Actions\ChangeAppointmentStatus;
use App\Modules\Appointment\Enums\AppointmentStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    use ResolvesCurrentSalon;

    public function index(Request $request): View
    {
        $salon = $this->currentSalon($request);

        $status = in_array($request->query('status'), AppointmentStatus::values(), true)
            ? $request->query('status')
            : null;

        $appointments = $salon->appointments()
            ->with(['service', 'stylist'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.appointments.index', compact('salon', 'appointments', 'status'));
    }

    public function updateStatus(UpdateAppointmentStatusRequest $request, Appointment $appointment, ChangeAppointmentStatus $changeStatus): RedirectResponse
    {
        $this->currentSalon($request);

        $status = $request->validated('status');

        $changeStatus->handle($appointment, $status);

        return back()->with('status', "Appointment {$appointment->reference} marked as {$status}.");
    }
}
