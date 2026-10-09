<?php

namespace App\Http\Requests\Api;

use App\Models\Conversation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * One guest message plus where in the UI the guest is when they send it.
 */
class SendMessageRequest extends FormRequest
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
        return [
            'guest_token' => ['required', 'uuid'],
            'message' => ['required', 'string', 'max:2000'],
            'scene' => ['nullable', Rule::in(Conversation::SCENES)],
            'selected_service' => ['nullable', 'string', 'max:120'],
            'selected_stylist' => ['nullable', 'string', 'max:120'],
            'selected_facility' => ['nullable', 'integer', 'min:1'],
            'appointment' => ['nullable', 'array'],
            'appointment.appointment_date' => ['nullable', 'date_format:Y-m-d'],
            'appointment.time_slot' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'appointment.occasion' => ['nullable', 'string', 'max:30'],
            'appointment.service_slug' => ['nullable', 'string', 'max:120'],
            'appointment.stylist_slug' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * Where the guest is in the UI, as the beautician's conversation remembers it.
     *
     * @return array{scene?: ?string, selected_service?: ?string, selected_stylist?: ?string, selected_facility?: ?int, appointment?: array<string, mixed>}
     */
    public function uiContext(): array
    {
        $data = $this->validated();

        return [
            'scene' => $data['scene'] ?? null,
            'selected_service' => $data['selected_service'] ?? ($data['appointment']['service_slug'] ?? null),
            'selected_facility' => $data['selected_facility'] ?? null,
            'selected_stylist' => $data['selected_stylist'] ?? ($data['appointment']['stylist_slug'] ?? null),
            ...array_key_exists('appointment', $data)
                ? ['appointment' => array_filter($data['appointment'] ?? [], fn ($value) => $value !== null && $value !== '')]
                : [],
        ];
    }
}
