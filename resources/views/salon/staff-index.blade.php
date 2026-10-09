<x-salon-stage :salon="$salon" :locale="$locale" :supported-locales="$supportedLocales" :labels="$labels" :stage-copy="$stageCopy" :narration="$narration" :scene="$scene" :backdrop="$backdrop" :nav-items="$navItems" :terms="$terms" :nav="false" :title="$labels['staff_heading'].' · '.$salon->name">
    <div class="stage-panels">
        <section class="stage-content staff-panel @container" aria-label="{{ $labels['staff_heading'] }}">
            <a href="{{ route('salon.show', ['salonSlug' => $salon->slug, 'lang' => $locale]) }}" data-stage-exit data-tour-line="{{ $narration['tour_home'] }}" class="panel-close" aria-label="{{ $stageCopy['back'] }}"><span aria-hidden="true">×</span></a>
            <p class="stage-eyebrow">{{ $salon->name }}</p>
            <h2>{{ $labels['staff_heading'] }}</h2>
            @include('salon.narrator', ['text' => $stageCopy['staff_intro'], 'key' => 'staff', 'inline' => true])

            <div class="mt-6 flex flex-wrap gap-3">
                <button type="button" data-hero-quick-message="{{ $labels['nav_staff_q'] }}" class="stage-action">{{ $labels['staff_heading'] }} <span aria-hidden="true">↗</span></button>
                @if($staffLinks['whatsapp_url'])<a href="{{ $staffLinks['whatsapp_url'] }}" target="_blank" rel="noopener" class="wizard-secondary">{{ $wizard['whatsapp'] }}</a>@endif
                @if($staffLinks['phone_url'])<a href="{{ $staffLinks['phone_url'] }}" class="wizard-secondary">{{ $wizard['call_salon'] }}</a>@endif
                @if($staffLinks['email_url'])<a href="{{ $staffLinks['email_url'] }}" class="wizard-secondary">{{ $wizard['email_salon'] }}</a>@endif
            </div>

            <div class="staff-location">
                <h3>{{ $stageCopy['location'] }}</h3>
                <p>{{ $salon->address }}, {{ $salon->city }}, {{ $salon->country }}</p>
                @if($salon->phone)<p>{{ $salon->phone }}</p>@endif
                @if($salon->email)<p>{{ $salon->email }}</p>@endif
                @if($salon->instagram)<p>{{ $salon->instagram }}</p>@endif
            </div>
        </section>
    </div>
</x-salon-stage>
