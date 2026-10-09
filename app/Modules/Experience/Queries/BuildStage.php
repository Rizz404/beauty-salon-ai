<?php

namespace App\Modules\Experience\Queries;

use App\Models\Salon;
use App\Models\SalonKnowledgeItem;
use App\Modules\Appointment\Support\AppointmentHandover;
use App\Modules\Appointment\Support\StylistAvailability;
use App\Modules\Experience\Presenters\SceneNarrations;
use App\Modules\Experience\Presenters\StageBackdrop;
use App\Modules\Experience\Presenters\StageCopy;
use App\Modules\Experience\Presenters\StageNavigation;

/**
 * Everything the shared stage shell needs, for whichever scene the guest has
 * walked into.
 */
class BuildStage
{
    public function __construct(
        private readonly AppointmentHandover $handover,
        private readonly StylistAvailability $availability,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(Salon $salon, string $locale, string $scene): array
    {
        $services = $salon->services()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->values();

        $stylists = $salon->stylists()
            ->where('is_active', true)
            ->with(['images' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->values();

        $facilities = $salon->knowledgeItems()->where('is_active', true)
            ->whereIn('category', [SalonKnowledgeItem::CATEGORY_FACILITIES, SalonKnowledgeItem::CATEGORY_EVENTS, SalonKnowledgeItem::CATEGORY_LOCATION])
            ->orderBy('sort_order')->get()->values();

        $labels = StageCopy::labels($locale);

        return [
            'salon' => $salon,
            'services' => $services,
            'stylists' => $stylists,
            'locale' => $locale,
            'scene' => $scene,
            'backdrop' => StageBackdrop::for($scene),
            'navItems' => StageNavigation::for($salon, $locale, $labels),
            'supportedLocales' => config('salon.locales'),
            'labels' => $labels,
            'stageCopy' => StageCopy::copy($locale),
            'terms' => StageCopy::terms($locale),
            'wizard' => StageCopy::wizard($locale),
            'narration' => StageCopy::narration($locale),
            'serviceNarrations' => SceneNarrations::services($salon, $services, $locale),
            'stylistNarrations' => SceneNarrations::stylists($salon, $stylists, $locale),
            'staffLinks' => $this->handover->forStaff($salon, $locale),
            'today' => now($salon->timezone)->toDateString(),
            'maxAdvanceDays' => StylistAvailability::MAX_ADVANCE_DAYS,
            'startTimes' => $this->availability->startTimesFor($salon),
            'infoItems' => $salon->knowledgeItems()->where('is_active', true)
                ->whereIn('category', [SalonKnowledgeItem::CATEGORY_GENERAL, SalonKnowledgeItem::CATEGORY_POLICIES, SalonKnowledgeItem::CATEGORY_FAQ])
                ->orderBy('sort_order')->get()->groupBy('category'),
            'facilities' => $facilities,
            'sceneNarrations' => SceneNarrations::scenes($salon, $facilities, $locale),
        ];
    }
}
