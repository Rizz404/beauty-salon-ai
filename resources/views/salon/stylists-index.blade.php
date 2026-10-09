<x-salon-stage :salon="$salon" :locale="$locale" :supported-locales="$supportedLocales" :labels="$labels" :stage-copy="$stageCopy" :narration="$narration" :scene="$scene" :backdrop="$backdrop" :nav-items="$navItems" :terms="$terms" :title="$labels['stylists_heading'].' · '.$salon->name">
    <x-slot:welcome>
        <div class="stage-welcome stage-welcome-scene" data-anchor="{{ $backdrop['text'] }}">
            <span class="stage-eyebrow">{{ $stageCopy['explore'] }}</span>
            <h1>{{ $labels['stylists_heading'] }}</h1>
            @if ($stylistNarrations['stylists'])
                @include('salon.narrator', ['text' => $stylistNarrations['stylists'], 'key' => 'stylists', 'onStage' => true])
            @else
                <p>{{ $stageCopy['stylists_empty'] }}</p>
            @endif
        </div>
    </x-slot:welcome>

    @if ($stylists->isNotEmpty())
        <x-slot:sidebar>@include('salon.stylists-nav')</x-slot:sidebar>
    @endif
</x-salon-stage>
