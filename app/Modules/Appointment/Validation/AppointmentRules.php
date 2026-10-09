<?php

namespace App\Modules\Appointment\Validation;

use App\Models\Appointment;
use App\Models\Salon;
use Illuminate\Validation\Rule;

/**
 * The one set of appointment rules, shared by the guest-facing wizard's form
 * requests so the quote step and the final submission can never disagree.
 */
final class AppointmentRules
{
    public const PHONE_PATTERN = '/^\+?[0-9\s\-().]{6,20}$/';

    public const CONTACT_TYPES = ['whatsapp', 'phone', 'email'];

    /**
     * What the guest asks for: which service, with whom, and when.
     *
     * @return array<string, mixed>
     */
    public static function slot(Salon $salon): array
    {
        $today = now($salon->timezone)->toDateString();

        return [
            'service_slug' => ['required', 'string', 'max:120'],
            'stylist_slug' => ['required', 'string', 'max:120'],
            'appointment_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$today],
            'time_slot' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'occasion' => ['nullable', Rule::in(Appointment::OCCASIONS)],
            'locale' => ['nullable', Rule::in(config('salon.locales'))],
        ];
    }

    /**
     * Who is asking, and how to reach them.
     *
     * @return array<string, mixed>
     */
    public static function guest(): array
    {
        return [
            'guest_name' => ['required', 'string', 'max:100'],
            'contact_type' => ['required', Rule::in(self::CONTACT_TYPES)],
            'contact_value' => ['required', 'string', 'max:120'],
            'special_request' => ['nullable', 'string', 'max:500'],
            'guest_token' => ['nullable', 'uuid'],
        ];
    }

    /**
     * Guest-facing wording: every generic rule failure reads the same.
     *
     * @return array<string, string>
     */
    public static function messages(string $locale): array
    {
        $messages = trans('appointment.messages', [], $locale);

        return [
            'required' => $messages['invalid'],
            'date_format' => $messages['invalid'],
            'integer' => $messages['invalid'],
            'min' => $messages['invalid'],
            'max' => $messages['invalid'],
            'regex' => $messages['invalid'],
            'in' => $messages['invalid'],
            'uuid' => $messages['invalid'],
            'after_or_equal' => $messages['date_past'],
        ];
    }

    /**
     * The guest-facing message for a domain failure, e.g. "stylist_mismatch".
     *
     * @param  array<string, string>  $replace
     */
    public static function message(string $key, string $locale, array $replace = []): string
    {
        return trans('appointment.messages.'.$key, $replace, $locale);
    }
}
