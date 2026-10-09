<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveSalon;
use App\Http\Requests\Api\QuoteAppointmentRequest;
use App\Http\Requests\Api\StoreAppointmentRequest;
use App\Models\Conversation;
use App\Models\Salon;
use App\Models\Service;
use App\Models\Stylist;
use App\Modules\Appointment\Actions\CreateAppointmentRequest;
use App\Modules\Appointment\Actions\ResolveAppointmentTarget;
use App\Modules\Appointment\Queries\FindAlternatives;
use App\Modules\Appointment\Support\AppointmentHandover;
use App\Modules\Appointment\Support\StylistAvailability;
use App\Modules\Appointment\Validation\AppointmentRules;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly StylistAvailability $availability,
        private readonly ResolveAppointmentTarget $resolveTarget,
        private readonly FindAlternatives $findAlternatives,
    ) {}

    /**
     * The start times at which the chosen service still fits in the chosen
     * stylist's day, so the wizard only offers times that can be booked.
     */
    public function slots(Request $request): JsonResponse
    {
        $salon = ResolveSalon::salon($request);
        $locale = ResolveSalon::locale($request);

        $data = $request->validate([
            'service_slug' => ['required', 'string', 'max:120'],
            'stylist_slug' => ['required', 'string', 'max:120'],
            'appointment_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.now($salon->timezone)->toDateString()],
            'locale' => ['nullable', Rule::in(config('salon.locales'))],
        ], AppointmentRules::messages($locale));

        [$service, $stylist, $date] = $this->resolveTarget->handle($salon, $data, $locale, checkTime: false);

        return response()->json([
            'date' => $date->toDateString(),
            'duration_minutes' => $service->duration_minutes,
            'slots' => array_map(
                fn (string $slot) => ['time_slot' => $slot, 'time_range' => StylistAvailability::range($slot, $service->duration_minutes)],
                $this->availability->availableStarts($stylist, $service, $date),
            ),
        ]);
    }

    public function quote(QuoteAppointmentRequest $request): JsonResponse
    {
        $salon = ResolveSalon::salon($request);
        $locale = ResolveSalon::locale($request);

        $data = $request->validated();
        [$service, $stylist, $date] = $this->resolveTarget->handle($salon, $data, $locale);

        $quote = $this->availability->quote($stylist, $service, $date, $data['time_slot']);

        if (! $quote) {
            return $this->unavailable($salon, $service, $stylist, $date, $data, $locale);
        }

        return response()->json([
            'available' => true,
            'date' => $quote['date'],
            'time_slot' => $quote['time_slot'],
            'time_range' => $quote['time_range'],
            'duration_minutes' => $quote['duration_minutes'],
            'price' => $quote['price'],
            'deposit' => $quote['deposit'],
            'currency' => $salon->currency,
        ]);
    }

    public function store(
        StoreAppointmentRequest $request,
        CreateAppointmentRequest $create,
        AppointmentHandover $handover,
    ): JsonResponse {
        $salon = ResolveSalon::salon($request);
        $locale = ResolveSalon::locale($request);

        $data = $request->validated();
        $isEmail = $request->isEmailContact();

        [$service, $stylist, $date] = $this->resolveTarget->handle($salon, $data, $locale);

        $conversation = isset($data['guest_token'])
            ? Conversation::where('salon_id', $salon->id)->where('guest_token', $data['guest_token'])->first()
            : null;

        $appointment = $create->handle($salon, $service, $stylist, [
            'appointment_date' => $date,
            'time_slot' => $data['time_slot'],
            'occasion' => $data['occasion'] ?? null,
            'guest_name' => $data['guest_name'],
            'guest_email' => $isEmail ? $data['contact_value'] : null,
            'guest_phone' => $isEmail ? null : $data['contact_value'],
            'contact_type' => $data['contact_type'],
            'notes' => $data['special_request'] ?? null,
        ], $conversation);

        if (! $appointment) {
            return $this->unavailable($salon, $service, $stylist, $date, $data, $locale);
        }

        return response()->json([
            'reference' => $appointment->reference,
            'status' => $appointment->status,
            'deposit' => (float) $appointment->deposit_total,
            'currency' => $salon->currency,
            'handover' => $handover->forAppointment($salon, $appointment, $locale),
        ], 201);
    }

    /**
     * The stylist is not free for the whole service at that time: say so, and
     * offer what is.
     *
     * @param  array<string, mixed>  $data
     */
    private function unavailable(Salon $salon, Service $service, Stylist $requested, CarbonImmutable $date, array $data, string $locale): JsonResponse
    {
        $message = AppointmentRules::message('unavailable', $locale);

        return response()->json([
            'message' => $message,
            'errors' => ['time_slot' => [$message]],
            'alternatives' => $this->findAlternatives->handle($service, $requested, $date, $data['time_slot'], $locale),
            'currency' => $salon->currency,
        ], 422);
    }
}
