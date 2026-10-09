<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Salon;
use App\Models\Service;
use App\Models\Stylist;
use App\Modules\Appointment\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => Appointment::generateReference(),
            'salon_id' => Salon::factory(),
            'service_id' => fn (array $attributes) => Service::factory()->create(['salon_id' => $attributes['salon_id']])->id,
            'stylist_id' => fn (array $attributes) => Stylist::factory()->create(['salon_id' => $attributes['salon_id']])->id,
            'guest_name' => fake()->name(),
            'guest_phone' => '+62 811 1111 222',
            'contact_type' => 'phone',
            'appointment_date' => now()->addDays(3)->toDateString(),
            'time_slot' => '11:00',
            'duration_minutes' => 60,
            'deposit_total' => 0,
            'status' => AppointmentStatus::Pending->value,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => AppointmentStatus::Confirmed->value]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => AppointmentStatus::Cancelled->value]);
    }
}
