<?php

namespace Database\Factories;

use App\Models\Salon;
use App\Models\SalonKnowledgeItem;
use App\Modules\Knowledge\Enums\KnowledgeCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalonKnowledgeItem>
 */
class SalonKnowledgeItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'salon_id' => Salon::factory(),
            'category' => KnowledgeCategory::General->value,
            'title' => fake()->sentence(3),
            'body' => fake()->paragraph(),
            'tags' => [],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inCategory(KnowledgeCategory $category): static
    {
        return $this->state(fn () => ['category' => $category->value]);
    }
}
