<x-layouts.admin title="Dashboard">
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ([
            'services' => 'Services',
            'stylists' => 'Stylists',
            'knowledge_items' => 'Knowledge items',
            'pending_appointments' => 'Pending appointments',
            'open_handovers' => 'Open handovers',
        ] as $key => $label)
            <div @class([
                'rounded-card border p-4',
                'border-warning-border bg-warning-soft' => $key === 'open_handovers' && $stats[$key] > 0,
                'border-border bg-surface' => ! ($key === 'open_handovers' && $stats[$key] > 0),
            ])>
                <p class="text-2xl font-semibold text-text">{{ $stats[$key] }}</p>
                <p class="text-xs text-muted">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card flush>
            <div class="flex items-center justify-between border-b border-border px-4 py-3">
                <p class="font-medium text-text">Open handovers</p>
                <a href="{{ route('admin.handovers.index') }}" class="text-xs text-muted hover:underline">View all</a>
            </div>
            <div class="divide-y divide-border">
                @forelse ($openHandovers as $handover)
                    <a href="{{ route('admin.handovers.show', $handover) }}" class="block px-4 py-3 hover:bg-surface-hover">
                        <p class="text-sm font-medium text-text">{{ ucwords(str_replace('_', ' ', $handover->reason)) }}</p>
                        <p class="mt-0.5 line-clamp-1 text-xs text-muted">{{ $handover->summary }}</p>
                    </a>
                @empty
                    <p class="px-4 py-6 text-center text-sm text-subtle">No open handovers.</p>
                @endforelse
            </div>
        </x-ui.card>

        <x-ui.card flush>
            <div class="flex items-center justify-between border-b border-border px-4 py-3">
                <p class="font-medium text-text">Recent appointments</p>
                <a href="{{ route('admin.appointments.index') }}" class="text-xs text-muted hover:underline">View all</a>
            </div>
            <div class="divide-y divide-border">
                @forelse ($recentAppointments as $appointment)
                    <div class="px-4 py-3">
                        <p class="text-sm font-medium text-text">{{ $appointment->guest_name }} — {{ $appointment->service->name }} with {{ $appointment->stylist->name }}</p>
                        <p class="mt-0.5 text-xs text-muted">{{ $appointment->appointment_date->toFormattedDateString() }} · {{ $appointment->time_range }} · {{ ucfirst($appointment->status) }}</p>
                    </div>
                @empty
                    <p class="px-4 py-6 text-center text-sm text-subtle">No appointments yet.</p>
                @endforelse
            </div>
        </x-ui.card>
    </div>
</x-layouts.admin>
