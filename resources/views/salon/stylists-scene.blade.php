@php
    $stylistName = $stylist->translatedName($locale);
    $primaryImage = $stylist->images->first();
    $stylistUrl = fn ($other) => route('salon.stylist', ['salonSlug' => $salon->slug, 'stylistSlug' => $other->slug, 'lang' => $locale]);
    $source = fn ($image) => $image->image_source;
    $performs = $services->where('category', $stylist->specialty);
@endphp
<section class="stage-content @container" aria-label="{{ $stylistName }}">
    <a href="{{ route('salon.stylists', ['salonSlug' => $salon->slug, 'lang' => $locale]) }}" data-stage-exit class="panel-close" aria-label="{{ $labels['stylists_heading'] }}"><span aria-hidden="true">×</span></a>
    <article class="detail-scene">
        <div class="flex flex-wrap items-center justify-between gap-3 pr-12">
            <p class="stage-eyebrow">{{ $stageCopy['stylist_counter'] }} {{ $stylistIndex + 1 }} / {{ $stylists->count() }}</p>
            @if ($stylists->count() > 1)
                <nav class="flex gap-2" aria-label="{{ $labels['stylists_heading'] }}">
                    <a href="{{ $stylistUrl($previousStylist) }}" data-stage-exit class="detail-scene-step" rel="prev"><span aria-hidden="true">←</span> {{ $stageCopy['prev_stylist'] }}</a>
                    <a href="{{ $stylistUrl($nextStylist) }}" data-stage-exit class="detail-scene-step" rel="next">{{ $stageCopy['next_stylist'] }} <span aria-hidden="true">→</span></a>
                </nav>
            @endif
        </div>

        @include('salon.narrator', ['text' => $stylistNarrations['stylist'][$stylist->slug], 'key' => 'stylist-'.$stylist->slug])

        <div class="mt-5 grid gap-7 @2xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
            <div data-detail-gallery>
                <div class="detail-photo">
                    @if ($primaryImage)
                        <img src="{{ $source($primaryImage) }}" alt="{{ $primaryImage->alt_text }}" data-detail-gallery-main>
                    @else
                        <div class="grid h-full place-items-center text-sm text-stone-500">{{ $stageCopy['no_photo'] }}</div>
                    @endif
                </div>
                @if ($stylist->images->count() > 1)
                    <ul class="mt-3 grid grid-cols-4 gap-2" aria-label="{{ $stageCopy['gallery'] }}">
                        @foreach ($stylist->images as $imageIndex => $image)
                            <li>
                                <button type="button" class="detail-scene-thumb" data-detail-thumb data-src="{{ $source($image) }}" data-alt="{{ $image->alt_text }}" aria-label="{{ $stageCopy['photo'] }} {{ $imageIndex + 1 }}" @if($imageIndex === 0) aria-current="true" @endif>
                                    <img src="{{ $source($image) }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="min-w-0">
                <h2 class="mt-0!">{{ $stylistName }}</h2>
                @if ($stylist->translatedTitle($locale))
                    <p class="mt-1 text-sm font-semibold text-stone-500">{{ $stylist->translatedTitle($locale) }}</p>
                @endif
                <p class="mt-3 leading-relaxed text-stone-600">{{ $stylist->translatedDescription($locale) }}</p>

                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('salon.appointment', ['salonSlug' => $salon->slug, 'lang' => $locale, 'stylist' => $stylist->slug]) }}" data-stage-exit data-tour-line="{{ $narration['tour_appointment'] }}" class="stage-action">{{ $stageCopy['book_stylist'] }} <span aria-hidden="true">↗</span></a>
                    <button type="button" class="detail-scene-step" data-hero-quick-message="{{ str_replace(':name', $stylistName, $stageCopy['ask_stylist_q']) }}">{{ $stageCopy['ask_stylist'] }}</button>
                </div>
                <p class="mt-3 text-xs text-stone-500">{{ $stageCopy['availability_note'] }}</p>

                <dl class="detail-scene-facts">
                    <div><dt>{{ $stageCopy['specialty'] }}</dt><dd>{{ $terms['specialty'][$stylist->specialty] ?? Str::headline($stylist->specialty) }}</dd></div>
                    <div><dt>{{ $stageCopy['experience'] }}</dt><dd>{{ $stylist->years_experience }} {{ $stageCopy['years_unit'] }}</dd></div>
                </dl>

                @if (! empty($stylist->skills))
                    <h3 class="mt-6 text-sm font-semibold">{{ $stageCopy['skills'] }}</h3>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach ($stylist->skills as $skill)
                            <li class="tag-pill">{{ Str::ucfirst($terms['skill'][$skill] ?? Str::headline($skill)) }}</li>
                        @endforeach
                    </ul>
                @endif

                @if ($performs->isNotEmpty())
                    <h3 class="mt-6 text-sm font-semibold">{{ $stageCopy['performs'] }}</h3>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach ($performs as $item)
                            <li><a href="{{ route('salon.service', ['salonSlug' => $salon->slug, 'serviceSlug' => $item->slug, 'lang' => $locale]) }}" data-stage-exit class="tag-pill">{{ $item->translatedName($locale) }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </article>
</section>
