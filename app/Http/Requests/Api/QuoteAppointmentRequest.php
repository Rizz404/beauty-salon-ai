<?php

namespace App\Http\Requests\Api;

use App\Http\Middleware\ResolveSalon;
use App\Modules\Appointment\Validation\AppointmentRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Where, when and how many: the first thing the appointment wizard checks.
 */
class QuoteAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return AppointmentRules::slot(ResolveSalon::salon($this));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return AppointmentRules::messages(ResolveSalon::locale($this));
    }
}
