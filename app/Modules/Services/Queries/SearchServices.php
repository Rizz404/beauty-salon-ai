<?php

namespace App\Modules\Services\Queries;

use App\Models\Salon;
use App\Models\Service;
use App\Support\SearchText;
use Illuminate\Support\Collection;

/**
 * The active service list, narrowed by keyword, category, need, budget or time.
 */
class SearchServices
{
    /**
     * @return Collection<int, Service>
     */
    public function handle(Salon $salon, string $query = '', ?string $category = null, ?string $need = null, ?float $maxPrice = null, ?int $maxMinutes = null, int $limit = 6): Collection
    {
        $query = SearchText::normalize($query);

        return $salon->services()
            ->where('is_active', true)
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (Service $item) => ! $need || $item->hasTag($need))
            ->filter(fn (Service $item) => $maxPrice === null || (float) $item->price <= $maxPrice)
            ->filter(fn (Service $item) => $maxMinutes === null || $item->duration_minutes <= $maxMinutes)
            ->filter(fn (Service $item) => SearchText::matches(implode(' ', [
                $item->name,
                $item->description,
                $item->category,
                implode(' ', $item->tags ?? []),
                collect($item->translations ?? [])->flatten()->implode(' '),
            ]), $query))
            ->take($limit);
    }
}
