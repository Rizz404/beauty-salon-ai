<?php

namespace Database\Factories;

use App\Models\Salon;
use App\Models\Service;
use App\Modules\Services\Enums\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'salon_id' => Salon::factory(),
            'category' => fake()->randomElement(ServiceCategory::values()),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(15, 90) * 10000,
            'duration_minutes' => 60,
            'deposit' => 0,
            'tags' => [],
            'cautions' => [],
            'is_featured' => false,
            'is_unavailable' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inCategory(ServiceCategory $category): static
    {
        return $this->state(fn () => ['category' => $category->value]);
    }

    public function lasting(int $minutes): static
    {
        return $this->state(fn () => ['duration_minutes' => $minutes]);
    }

    public function withDeposit(float $deposit): static
    {
        return $this->state(fn () => ['deposit' => $deposit]);
    }

    public function unavailable(): static
    {
        return $this->state(fn () => ['is_unavailable' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
