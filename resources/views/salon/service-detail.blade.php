<x-salon-stage :salon="$salon" :locale="$locale" :supported-locales="$supportedLocales" :labels="$labels" :stage-copy="$stageCopy" :narration="$narration" :scene="$scene" :backdrop="$backdrop" :nav-items="$navItems" :terms="$terms" :title="$service->translatedName($locale).' · '.$salon->name">
    @if($services->isNotEmpty())
        <x-slot:sidebar>@include('salon.services-nav')</x-slot:sidebar>
    @endif

    <div class="stage-panels">
        @include('salon.services-scene')
    </div>
</x-salon-stage>
