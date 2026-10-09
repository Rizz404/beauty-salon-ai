<?php

namespace App\Http\Requests\Admin;

use App\Modules\Appointment\Enums\AppointmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * The salon team confirms or cancels a pending appointment; it never goes back
 * to pending.
 */
class UpdateAppointmentStatusRequest extends FormRequest
{
    /**
     * A record of another salon is "not found" before anything is validated.
     */
    public function authorize(): bool
    {
        $record = $this->route('appointment');

        if ($record) {
            Gate::authorize('update', $record);
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([AppointmentStatus::Confirmed->value, AppointmentStatus::Cancelled->value])],
        ];
    }
}
