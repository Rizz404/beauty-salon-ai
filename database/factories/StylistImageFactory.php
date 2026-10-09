<?php

namespace Database\Factories;

use App\Models\Stylist;
use App\Models\StylistImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StylistImage>
 */
class StylistImageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stylist_id' => Stylist::factory(),
            'image_url' => 'images/stylists/indoor.svg',
            'alt_text' => fake()->sentence(3),
            'tags' => [],
            'sort_order' => 0,
        ];
    }
}
