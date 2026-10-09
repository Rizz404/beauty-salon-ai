<?php

namespace App\Modules\Stylists\Actions;

use App\Models\Salon;
use App\Models\Stylist;
use Illuminate\Support\Str;

/**
 * Creates a stylist or updates one, together with the portfolio photos added or
 * removed in the same form. A new stylist gets a slug from their name; an
 * existing one keeps their slug so guest links never break.
 */
class SaveStylist
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $imageIdsToDelete
     * @param  list<array{url: string, alt?: ?string, tags?: ?string}>  $newImages
     */
    public function handle(Salon $salon, array $attributes, array $imageIdsToDelete, array $newImages, ?Stylist $stylist = null): Stylist
    {
        if ($stylist) {
            $stylist->update($attributes);
        } else {
            $stylist = $salon->stylists()->create([...$attributes, 'slug' => $this->uniqueSlug($salon, $attributes['name'])]);
        }

        $this->syncImages($stylist, $imageIdsToDelete, $newImages);

        return $stylist;
    }

    private function uniqueSlug(Salon $salon, string $name): string
    {
        $base = Str::slug($name) ?: 'stylist';
        $slug = $base;
        $suffix = 1;

        while ($salon->stylists()->withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @param  list<int>  $deleteIds
     * @param  list<array{url: string, alt?: ?string, tags?: ?string}>  $newImages
     */
    private function syncImages(Stylist $stylist, array $deleteIds, array $newImages): void
    {
        if ($deleteIds !== []) {
            $stylist->images()->whereIn('id', $deleteIds)->delete();
        }

        $sort = $stylist->images()->max('sort_order') + 1;

        foreach ($newImages as $row) {
            $stylist->images()->create([
                'image_url' => $row['url'],
                'alt_text' => $row['alt'] ?? $stylist->name,
                'tags' => collect(explode(',', $row['tags'] ?? ''))->map(fn ($t) => trim($t))->filter()->values()->all(),
                'sort_order' => $sort++,
            ]);
        }
    }
}
