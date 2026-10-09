{{-- On the service scenes the bottom bar becomes the service index, so the guest can step between treatments without going back. --}}
<aside class="stage-menu" aria-label="{{ $labels['services_heading'] }}">
    <a href="{{ route('salon.show', ['salonSlug' => $salon->slug, 'lang' => $locale]) }}" data-stage-exit data-tour-line="{{ $narration['tour_home'] }}" class="stage-menu-back"><span aria-hidden="true">‹</span> {{ $stageCopy['explore'] }}</a>
    <p class="stage-eyebrow">{{ $labels['services_heading'] }}</p>
    <nav class="item-nav">
        @foreach ($services->groupBy('category') as $category => $group)
            <p class="item-nav-heading">{{ $terms['category'][$category] ?? Str::headline($category) }}</p>
            @foreach ($group as $item)
                <a href="{{ route('salon.service', ['salonSlug' => $salon->slug, 'serviceSlug' => $item->slug, 'lang' => $locale]) }}"
                    data-stage-exit
                    @if (($service ?? null)?->slug === $item->slug) aria-current="page" @endif>
                    @if ($item->image_source)
                        <img class="item-nav-thumb" src="{{ $item->image_source }}" alt="" loading="lazy">
                    @endif
                    <span class="item-nav-text">
                        <span class="item-nav-name">{{ $item->translatedName($locale) }}</span>
                        <span class="item-nav-price">{{ $salon->currency }} {{ number_format((float) $item->price, 0, ',', '.') }} · {{ \App\Modules\Experience\Presenters\SceneNarrations::duration($item->duration_minutes, $locale) }}@if ($item->is_unavailable)<span class="item-nav-badge">{{ $labels['unavailable'] }}</span>@endif</span>
                    </span>
                </a>
            @endforeach
        @endforeach
    </nav>
    <span class="stage-menu-footer">{{ $stageCopy['available'] }} · BEAUTY SALON AI</span>
</aside>
