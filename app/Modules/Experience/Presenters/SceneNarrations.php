<?php

namespace App\Modules\Experience\Presenters;

use App\Models\Salon;
use App\Models\SalonKnowledgeItem;
use App\Models\Service;
use App\Models\Stylist;
use Illuminate\Support\Collection;

/**
 * What the AI Beautician says when the guest steps into a scene. Every sentence
 * is built from stored salon data, never generated.
 */
final class SceneNarrations
{
    /**
     * @param  Collection<int, Service>  $services
     * @return array{services: ?string, service: array<string, string>}
     */
    public static function services(Salon $salon, Collection $services, string $locale): array
    {
        $templates = StageCopy::narration($locale);
        $terms = StageCopy::terms($locale);
        $money = self::money($salon);
        $sentence = self::sentence($locale);
        $separator = $locale === 'ja' ? '、' : ', ';

        if ($services->isEmpty()) {
            return ['services' => null, 'service' => []];
        }

        $intro = str_replace(
            [':count', ':price'],
            [(string) $services->count(), $money($services->min('price'))],
            $templates[$services->count() === 1 ? 'services_one' : 'services']
        );

        $narrations = $services->mapWithKeys(function (Service $service) use ($templates, $terms, $money, $sentence, $separator, $locale) {
            $parts = [$service->translatedName($locale).'.', filled($service->translatedDescription($locale)) ? $sentence(trim($service->translatedDescription($locale))) : null];

            $parts[] = str_replace(':duration', self::duration($service->duration_minutes, $locale), $templates['service_duration']);

            if (! empty($service->cautions)) {
                $list = collect($service->cautions)->map(fn ($caution) => $terms['caution'][$caution] ?? $caution)->implode($separator);
                $parts[] = str_replace(':list', $list, $templates['service_cautions']);
            }

            $parts[] = str_replace(':price', $money($service->price), $templates['service_price']);

            if ((float) $service->deposit > 0) {
                $parts[] = str_replace(':deposit', $money($service->deposit), $templates['service_deposit']);
            }

            if ($service->is_unavailable) {
                $parts[] = $templates['service_unavailable'];
            }

            return [$service->slug => implode(' ', array_filter($parts))];
        })->all();

        return ['services' => $intro, 'service' => $narrations];
    }

    /**
     * @param  Collection<int, Stylist>  $stylists
     * @return array{stylists: ?string, stylist: array<string, string>}
     */
    public static function stylists(Salon $salon, Collection $stylists, string $locale): array
    {
        $templates = StageCopy::narration($locale);
        $terms = StageCopy::terms($locale);
        $sentence = self::sentence($locale);
        $separator = $locale === 'ja' ? '、' : ', ';

        if ($stylists->isEmpty()) {
            return ['stylists' => null, 'stylist' => []];
        }

        $first = $stylists->first()->translatedName($locale);
        $intro = str_replace(
            [':count', ':first'],
            [(string) $stylists->count(), $first],
            $templates[$stylists->count() === 1 ? 'stylists_one' : 'stylists']
        );

        $narrations = $stylists->mapWithKeys(function (Stylist $stylist) use ($templates, $terms, $sentence, $separator, $locale) {
            $parts = [$stylist->translatedName($locale).'.', filled($stylist->translatedDescription($locale)) ? $sentence(trim($stylist->translatedDescription($locale))) : null];

            $parts[] = str_replace(':specialty', $terms['specialty'][$stylist->specialty] ?? $stylist->specialty, $templates['stylist_specialty']);

            $parts[] = $stylist->years_experience > 1
                ? str_replace(':years', (string) $stylist->years_experience, $templates['stylist_experience'])
                : $templates['stylist_experience_one'];

            if (! empty($stylist->skills)) {
                $list = collect($stylist->skills)->map(fn ($skill) => $terms['skill'][$skill] ?? $skill)->implode($separator);
                $parts[] = str_replace(':list', $list, $templates['stylist_skills']);
            }

            return [$stylist->slug => implode(' ', array_filter($parts))];
        })->all();

        return ['stylists' => $intro, 'stylist' => $narrations];
    }

    /**
     * Intros for the facilities list and the salon information scene, built
     * only from stored salon data.
     *
     * @param  Collection<int, SalonKnowledgeItem>  $facilities
     * @return array{facilities: ?string, info: string}
     */
    public static function scenes(Salon $salon, Collection $facilities, string $locale): array
    {
        $templates = StageCopy::narration($locale);
        $separator = $locale === 'ja' ? '、' : '; ';
        $time = fn (?string $value) => $value ? substr($value, 0, 5) : null;

        $intro = null;

        if ($facilities->isNotEmpty()) {
            $examples = $facilities->take(3)->map(fn ($item) => $item->translatedTitle($locale))->implode($separator);
            $intro = str_replace(
                [':count', ':examples'],
                [(string) $facilities->count(), $examples],
                $templates[$facilities->count() === 1 ? 'facilities_one' : 'facilities']
            );
        }

        $location = collect([$salon->city, $salon->country])->filter()->implode($locale === 'ja' ? '、' : ', ');
        $parts = [str_replace(':salon', $salon->name, $templates['info_welcome'])];

        if ($location !== '') {
            $parts[] = str_replace(':location', $location, $templates['info_place']);
        }

        if ($time($salon->opening_time) && $time($salon->closing_time)) {
            $parts[] = str_replace([':open', ':close'], [$time($salon->opening_time), $time($salon->closing_time)], $templates['info_hours']);
        }

        $parts[] = $templates['info_more'];

        return ['facilities' => $intro, 'info' => implode(' ', $parts)];
    }

    /**
     * "1 h 30 min" in the guest's language, for a duration in minutes.
     */
    public static function duration(int $minutes, string $locale): string
    {
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        $units = match ($locale) {
            'id' => ['jam', 'menit'],
            'ja' => ['時間', '分'],
            default => ['h', 'min'],
        };

        $glue = $locale === 'ja' ? '' : ' ';

        return collect([
            $hours > 0 ? $hours.$glue.$units[0] : null,
            $rest > 0 ? $rest.$glue.$units[1] : null,
        ])->filter()->implode($glue === '' ? '' : ' ');
    }

    /**
     * @return \Closure(mixed): string
     */
    private static function money(Salon $salon): \Closure
    {
        return fn ($value) => $salon->currency.' '.number_format((float) $value, 0, ',', '.');
    }

    /**
     * @return \Closure(string): string
     */
    private static function sentence(string $locale): \Closure
    {
        return fn (string $text) => preg_match('/[.!?。！？]$/u', $text) ? $text : $text.($locale === 'ja' ? '。' : '.');
    }
}
