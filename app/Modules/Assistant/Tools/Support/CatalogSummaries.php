<?php

namespace App\Modules\Assistant\Tools\Support;

use App\Models\Salon;
use App\Models\Service;
use App\Models\Stylist;

/**
 * The compact facts about a service or a stylist that both the model and the
 * guest's cards are given.
 */
final class CatalogSummaries
{
    /**
     * @return array<string, mixed>
     */
    public static function service(Service $service, Salon $salon, string $locale): array
    {
        return [
            'service_slug' => $service->slug,
            'name' => $service->translatedName($locale),
            'category' => $service->category,
            'price' => (float) $service->price,
            'duration_minutes' => $service->duration_minutes,
            'deposit' => (float) $service->deposit,
            'currency' => $salon->currency,
            'tags' => $service->tags ?? [],
            'cautions' => $service->cautions ?? [],
            'is_unavailable' => $service->is_unavailable,
            'image_url' => $service->image_source,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function stylist(Stylist $stylist, Salon $salon, string $locale): array
    {
        return [
            'stylist_slug' => $stylist->slug,
            'name' => $stylist->translatedName($locale),
            'title' => $stylist->translatedTitle($locale),
            'specialty' => $stylist->specialty,
            'years_experience' => $stylist->years_experience,
            'skills' => $stylist->skills ?? [],
            'currency' => $salon->currency,
            'thumbnail_url' => $stylist->images->first()?->image_source,
        ];
    }

    public static function serviceBySlug(Salon $salon, string $slug): ?Service
    {
        return $salon->services()->where('is_active', true)->where('slug', $slug)->first();
    }

    public static function stylistBySlug(Salon $salon, string $slug): ?Stylist
    {
        return $salon->stylists()->where('is_active', true)->where('slug', $slug)->first();
    }
}
