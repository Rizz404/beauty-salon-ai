<?php

namespace Database\Factories;

use App\Models\Salon;
use App\Models\Stylist;
use App\Modules\Services\Enums\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Stylist>
 */
class StylistFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->name();

        return [
            'salon_id' => Salon::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'title' => 'Senior stylist',
            'description' => fake()->sentence(),
            'specialty' => ServiceCategory::Hair->value,
            'years_experience' => 5,
            'skills' => [],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function specializing(ServiceCategory $category): static
    {
        return $this->state(fn () => ['specialty' => $category->value]);
    }
}
