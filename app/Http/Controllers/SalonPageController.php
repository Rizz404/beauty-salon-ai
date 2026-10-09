<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ResolveSalon;
use App\Models\Salon;
use App\Models\SalonKnowledgeItem;
use App\Models\Service;
use App\Models\Stylist;
use App\Modules\Experience\Presenters\StageBackdrop;
use App\Modules\Experience\Presenters\StageCopy;
use App\Modules\Experience\Queries\BuildStage;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The guest-facing stage: one page per scene, all built from the same shell
 * data. The salon and the language are resolved by the ResolveSalon middleware.
 */
class SalonPageController extends Controller
{
    public function __construct(private readonly BuildStage $stage) {}

    public function index(Request $request): View
    {
        $salons = Salon::published()->orderBy('name')->get();

        $locale = in_array($request->query('lang'), config('salon.locales'), true)
            ? $request->query('lang')
            : ($salons->first()?->default_locale ?? config('salon.default_locale'));

        return view('opening', [
            'salons' => $salons,
            'locale' => $locale,
            'supportedLocales' => config('salon.locales'),
            'opening' => StageCopy::opening($locale),
            'avatarImage' => StageBackdrop::avatar(),
            'backdrop' => StageBackdrop::for('home'),
        ]);
    }

    public function show(Request $request): View
    {
        return view('salon.show', $this->stageData($request, 'home'));
    }

    public function services(Request $request): View
    {
        return view('salon.services-index', $this->stageData($request, 'services'));
    }

    public function service(Request $request, string $salonSlug, string $serviceSlug): View
    {
        $data = $this->stageData($request, 'service');
        $services = $data['services'];
        $index = $services->search(fn (Service $service) => $service->slug === $serviceSlug);

        abort_if($index === false, 404);

        return view('salon.service-detail', [
            ...$data,
            'service' => $services[$index],
            'serviceIndex' => $index,
            ...$this->neighbours($services, $index, 'Service'),
        ]);
    }

    public function stylists(Request $request): View
    {
        return view('salon.stylists-index', $this->stageData($request, 'stylists'));
    }

    public function stylist(Request $request, string $salonSlug, string $stylistSlug): View
    {
        $data = $this->stageData($request, 'stylist');
        $stylists = $data['stylists'];
        $index = $stylists->search(fn (Stylist $stylist) => $stylist->slug === $stylistSlug);

        abort_if($index === false, 404);

        return view('salon.stylist-detail', [
            ...$data,
            'stylist' => $stylists[$index],
            'stylistIndex' => $index,
            ...$this->neighbours($stylists, $index, 'Stylist'),
        ]);
    }

    public function facilities(Request $request): View
    {
        return view('salon.facilities-index', $this->stageData($request, 'facilities'));
    }

    public function facility(Request $request, string $salonSlug, int $facilityId): View
    {
        $data = $this->stageData($request, 'facility');
        $facilities = $data['facilities'];
        $index = $facilities->search(fn (SalonKnowledgeItem $item) => $item->id === $facilityId);

        abort_if($index === false, 404);

        return view('salon.facility-detail', [
            ...$data,
            'facility' => $facilities[$index],
            'facilityIndex' => $index,
            ...$this->neighbours($facilities, $index, 'Facility'),
        ]);
    }

    public function info(Request $request): View
    {
        return view('salon.info-index', $this->stageData($request, 'info'));
    }

    public function staff(Request $request): View
    {
        return view('salon.staff-index', $this->stageData($request, 'staff'));
    }

    public function appointmentScene(Request $request): View
    {
        return view('salon.appointment-index', [
            ...$this->stageData($request, 'appointment'),
            'preselectedService' => (string) $request->query('service', ''),
            'preselectedStylist' => (string) $request->query('stylist', ''),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function stageData(Request $request, string $scene): array
    {
        return $this->stage->handle(ResolveSalon::salon($request), ResolveSalon::locale($request), $scene);
    }

    /**
     * The previous and next entry around an index, wrapping at both ends.
     *
     * @param  Collection<int, mixed>  $items
     * @return array<string, mixed>
     */
    private function neighbours(Collection $items, int $index, string $suffix): array
    {
        return [
            'previous'.$suffix => $items[($index - 1 + $items->count()) % $items->count()],
            'next'.$suffix => $items[($index + 1) % $items->count()],
        ];
    }
}
