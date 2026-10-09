<?php

namespace App\Modules\Knowledge\Queries;

use App\Models\Salon;
use App\Models\SalonKnowledgeItem;
use App\Support\SearchText;
use Illuminate\Support\Collection;

/**
 * The salon's approved knowledge base, searched by keyword. The only source the
 * AI Beautician may state salon facts from.
 */
class SearchKnowledge
{
    /**
     * @return Collection<int, SalonKnowledgeItem>
     */
    public function handle(Salon $salon, string $query, ?string $category = null, int $limit = 5): Collection
    {
        $query = SearchText::normalize($query);

        return $salon->knowledgeItems()
            ->where('is_active', true)
            ->when($category, fn ($q) => $q->where('category', $category))
            ->get()
            ->filter(fn (SalonKnowledgeItem $item) => SearchText::matches(
                $item->title.' '.$item->body.' '.implode(' ', $item->tags ?? []),
                $query,
            ))
            ->take($limit);
    }
}
