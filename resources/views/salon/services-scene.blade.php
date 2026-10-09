@php
    $serviceName = $service->translatedName($locale);
    $serviceUrl = fn ($other) => route('salon.service', ['salonSlug' => $salon->slug, 'serviceSlug' => $other->slug, 'lang' => $locale]);
    $knownTags = collect($service->tags ?? [])->filter(fn ($tag) => isset($terms['tag'][$tag]));
    $duration = \App\Modules\Experience\Presenters\SceneNarrations::duration($service->duration_minutes, $locale);
@endphp
<section class="stage-content @container" aria-label="{{ $serviceName }}">
    <a href="{{ route('salon.services', ['salonSlug' => $salon->slug, 'lang' => $locale]) }}" data-stage-exit class="panel-close" aria-label="{{ $labels['services_heading'] }}"><span aria-hidden="true">×</span></a>
    <article class="detail-scene">
        <div class="flex flex-wrap items-center justify-between gap-3 pr-12">
            <p class="stage-eyebrow">{{ $stageCopy['service_counter'] }} {{ $serviceIndex + 1 }} / {{ $services->count() }}</p>
            @if ($services->count() > 1)
                <nav class="flex gap-2" aria-label="{{ $labels['services_heading'] }}">
                    <a href="{{ $serviceUrl($previousService) }}" data-stage-exit class="detail-scene-step" rel="prev"><span aria-hidden="true">←</span> {{ $stageCopy['prev_service'] }}</a>
                    <a href="{{ $serviceUrl($nextService) }}" data-stage-exit class="detail-scene-step" rel="next">{{ $stageCopy['next_service'] }} <span aria-hidden="true">→</span></a>
                </nav>
            @endif
        </div>

        @include('salon.narrator', ['text' => $serviceNarrations['service'][$service->slug], 'key' => 'service-'.$service->slug])

        <div class="mt-5 grid gap-7 @2xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
            <div class="detail-photo">
                @if ($service->image_source)
                    <img src="{{ $service->image_source }}" alt="{{ $serviceName }}">
                @else
                    <div class="grid h-full place-items-center text-sm text-stone-500">{{ $stageCopy['no_photo'] }}</div>
                @endif
                @if ($service->is_unavailable)
                    <span class="detail-badge">{{ $stageCopy['unavailable'] }}</span>
                @endif
            </div>

            <div class="min-w-0">
                <h2 class="mt-0!">{{ $serviceName }}</h2>
                <p class="mt-3 leading-relaxed text-stone-600">{{ $service->translatedDescription($locale) }}</p>

                <p class="detail-price mt-5">{{ $salon->currency }} {{ number_format((float) $service->price, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-stone-500">{{ $stageCopy['price_note'] }}</p>

                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('salon.appointment', ['salonSlug' => $salon->slug, 'lang' => $locale, 'service' => $service->slug]) }}" data-stage-exit data-tour-line="{{ $narration['tour_appointment'] }}" class="stage-action">{{ $stageCopy['book_service'] }} <span aria-hidden="true">↗</span></a>
                    <button type="button" class="detail-scene-step" data-hero-quick-message="{{ str_replace(':name', $serviceName, $stageCopy['ask_service_q']) }}">{{ $stageCopy['ask_service'] }}</button>
                </div>

                <dl class="detail-scene-facts">
                    <div><dt>{{ $stageCopy['category'] }}</dt><dd>{{ $terms['category'][$service->category] ?? Str::headline($service->category) }}</dd></div>
                    <div><dt>{{ $stageCopy['duration'] }}</dt><dd>{{ $duration }}</dd></div>
                    <div><dt>{{ $stageCopy['deposit'] }}</dt><dd>@if ((float) $service->deposit > 0){{ $salon->currency }} {{ number_format((float) $service->deposit, 0, ',', '.') }}@else{{ $stageCopy['deposit_free'] }}@endif</dd></div>
                </dl>

                @if ($knownTags->isNotEmpty())
                    <ul class="mt-5 flex flex-wrap gap-2">
                        @foreach ($knownTags as $tag)
                            <li class="tag-pill {{ in_array($tag, ['signature', 'bestseller'], true) ? 'is-accent' : '' }}">{{ $terms['tag'][$tag] }}</li>
                        @endforeach
                    </ul>
                @endif

                @if (! empty($service->cautions))
                    <h3 class="mt-6 text-sm font-semibold">{{ $stageCopy['cautions'] }}</h3>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach ($service->cautions as $caution)
                            <li class="tag-pill is-alert">{{ Str::ucfirst($terms['caution'][$caution] ?? $caution) }}</li>
                        @endforeach
                    </ul>
                @endif
                <p class="detail-note">{{ $stageCopy['caution_note'] }}</p>
            </div>
        </div>
    </article>
</section>
