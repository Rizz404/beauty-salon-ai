<?php

namespace Tests\Feature;

use App\Models\Salon;
use App\Modules\Knowledge\Queries\SearchKnowledge;
use Database\Seeders\SalonKnowledgeItemSeeder;
use Database\Seeders\SalonSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpeningHoursKnowledgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_knowledge_base_answers_opening_hour_questions_in_each_language(): void
    {
        $this->seed(SalonSeeder::class);
        $this->seed(SalonKnowledgeItemSeeder::class);
        $salon = Salon::where('name', SalonSeeder::NAME)->firstOrFail();
        $search = app(SearchKnowledge::class);

        foreach (['jam buka' => 'Jam buka salon', 'opening hours' => 'Jam buka salon', '営業時間' => 'Jam buka salon'] as $query => $title) {
            $this->assertContains($title, $search->handle($salon, $query)->pluck('title')->all(), "No opening hours entry for '{$query}'.");
        }

        $item = $salon->knowledgeItems()->where('title', 'Jam buka salon')->firstOrFail();
        $this->assertStringContainsString(substr((string) $salon->opening_time, 0, 5), $item->translatedBody('en'));
        $this->assertStringContainsString(substr((string) $salon->closing_time, 0, 5), $item->translatedBody('en'));
    }

    public function test_reseeding_does_not_duplicate_the_entry(): void
    {
        $this->seed(SalonSeeder::class);
        $this->seed(SalonKnowledgeItemSeeder::class);
        $this->seed(SalonKnowledgeItemSeeder::class);

        $this->assertSame(1, Salon::firstOrFail()->knowledgeItems()->where('title', 'Jam buka salon')->count());
    }
}
