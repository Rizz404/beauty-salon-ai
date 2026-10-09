<x-salon-stage :salon="$salon" :locale="$locale" :supported-locales="$supportedLocales" :labels="$labels" :stage-copy="$stageCopy" :narration="$narration" :scene="$scene" :backdrop="$backdrop" :nav-items="$navItems" :terms="$terms" :nav="false" :title="$facility->translatedTitle($locale).' · '.$salon->name">
    @php
        $title = $facility->translatedTitle($locale);
        $facilityUrl = fn ($item) => route('salon.facility', ['salonSlug' => $salon->slug, 'facilityId' => $item->id, 'lang' => $locale]);
    @endphp

    <div class="stage-panels">
        <section class="stage-content stage-panel-right @container" aria-label="{{ $title }}">
            <a href="{{ route('salon.facilities', ['salonSlug' => $salon->slug, 'lang' => $locale]) }}" data-stage-exit class="panel-close" aria-label="{{ $stageCopy['back_facilities'] }}"><span aria-hidden="true">×</span></a>
            <article class="detail-scene">
                <div class="flex flex-wrap items-center justify-between gap-3 pr-12">
                    <p class="stage-eyebrow">{{ $stageCopy['facility_counter'] }} {{ $facilityIndex + 1 }} / {{ $facilities->count() }}</p>
                    @if ($facilities->count() > 1)
                        <nav class="flex gap-2" aria-label="{{ $labels['facilities_heading'] }}">
                            <a href="{{ $facilityUrl($previousFacility) }}" data-stage-exit class="detail-scene-step" rel="prev"><span aria-hidden="true">←</span> {{ $stageCopy['prev_facility'] }}</a>
                            <a href="{{ $facilityUrl($nextFacility) }}" data-stage-exit class="detail-scene-step" rel="next">{{ $stageCopy['next_facility'] }} <span aria-hidden="true">→</span></a>
                        </nav>
                    @endif
                </div>
                @if ($facility->image_source)
                    <div class="facility-hero mt-5">
                        <img src="{{ $facility->image_source }}" alt="{{ $title }}">
                        <span class="facility-card-icon"><x-facility-icon :name="$facility->iconName()" /></span>
                    </div>
                @endif
                <h2>{{ $title }}</h2>
                @include('salon.narrator', ['text' => $facility->translatedBody($locale), 'key' => 'facility-'.$facility->id, 'inline' => true])
                <div class="mt-8 flex flex-wrap gap-3">
                    <button type="button" class="stage-action" data-hero-quick-message="{{ str_replace(':name', $title, $stageCopy['ask_facility_q']) }}">{{ $stageCopy['ask_facility'] }} <span aria-hidden="true">↗</span></button>
                    <a href="{{ route('salon.facilities', ['salonSlug' => $salon->slug, 'lang' => $locale]) }}" data-stage-exit class="detail-scene-step">{{ $stageCopy['back_facilities'] }}</a>
                </div>
            </article>
        </section>
    </div>
</x-salon-stage>
