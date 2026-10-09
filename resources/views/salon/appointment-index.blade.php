<x-salon-stage :salon="$salon" :locale="$locale" :supported-locales="$supportedLocales" :labels="$labels" :stage-copy="$stageCopy" :narration="$narration" :scene="$scene" :backdrop="$backdrop" :nav-items="$navItems" :terms="$terms" :nav="false" :title="$labels['appointment_heading'].' · '.$salon->name">
    <div class="stage-panels">
        @include('salon.appointment')
    </div>
</x-salon-stage>
