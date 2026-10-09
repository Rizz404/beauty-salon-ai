<?php

namespace Database\Factories;

use App\Models\Salon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Salon>
 */
class SalonFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company().' Salon';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'city' => fake()->city(),
            'country' => 'Indonesia',
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
            'default_locale' => 'id',
            'opening_time' => '09:00',
            'closing_time' => '20:00',
            'public_status' => 'published',
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['public_status' => 'draft']);
    }
}
