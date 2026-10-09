<x-salon-stage :salon="$salon" :locale="$locale" :supported-locales="$supportedLocales" :labels="$labels" :stage-copy="$stageCopy" :narration="$narration" :scene="$scene" :backdrop="$backdrop" :nav-items="$navItems" :terms="$terms" :title="$stylist->translatedName($locale).' · '.$salon->name">
    @if($stylists->isNotEmpty())
        <x-slot:sidebar>@include('salon.stylists-nav')</x-slot:sidebar>
    @endif

    <div class="stage-panels">
        @include('salon.stylists-scene')
    </div>
</x-salon-stage>
