{{-- On the stylist scenes the bottom bar becomes the team index, so the guest can step between stylists without going back. --}}
<aside class="stage-menu" aria-label="{{ $labels['stylists_heading'] }}">
    <a href="{{ route('salon.show', ['salonSlug' => $salon->slug, 'lang' => $locale]) }}" data-stage-exit data-tour-line="{{ $narration['tour_home'] }}" class="stage-menu-back"><span aria-hidden="true">‹</span> {{ $stageCopy['explore'] }}</a>
    <p class="stage-eyebrow">{{ $labels['stylists_heading'] }}</p>
    <nav class="item-nav">
        @foreach ($stylists as $item)
            <a href="{{ route('salon.stylist', ['salonSlug' => $salon->slug, 'stylistSlug' => $item->slug, 'lang' => $locale]) }}"
                data-stage-exit
                @if (($stylist ?? null)?->slug === $item->slug) aria-current="page" @endif>
                @if ($item->images->first())
                    <img class="item-nav-thumb" src="{{ $item->images->first()->image_source }}" alt="" loading="lazy">
                @endif
                <span class="item-nav-text">
                    <span class="item-nav-name">{{ $item->translatedName($locale) }}</span>
                    <span class="item-nav-price">{{ $terms['specialty'][$item->specialty] ?? Str::headline($item->specialty) }} · {{ $item->years_experience }} {{ $stageCopy['years_unit'] }}</span>
                </span>
            </a>
        @endforeach
    </nav>
    <span class="stage-menu-footer">{{ $stageCopy['available'] }} · BEAUTY SALON AI</span>
</aside>
