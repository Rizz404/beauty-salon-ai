<x-layouts.admin title="Appointments">
    <div class="mb-4 flex gap-2 text-sm">
        @foreach (['' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled'] as $value => $label)
            <a href="{{ route('admin.appointments.index', $value ? ['status' => $value] : []) }}"
                class="rounded-full px-3 py-1 {{ $status === $value || (! $status && $value === '') ? 'bg-primary text-on-primary' : 'border border-border-strong bg-surface text-muted' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <x-ui.table :columns="['Reference', 'Guest', 'Treatment', 'When', 'Deposit', 'Status', '']" :empty="$appointments->isEmpty()" empty-text="No appointments found.">
        @foreach ($appointments as $appointment)
            <tr>
                <td class="px-4 py-3 font-mono text-xs">{{ $appointment->reference }}</td>
                <td class="px-4 py-3">
                    <p class="font-medium text-text">{{ $appointment->guest_name }}</p>
                    <p class="text-xs text-muted">{{ $appointment->contact_type ? ucfirst($appointment->contact_type).': ' : '' }}{{ $appointment->guest_phone ?? $appointment->guest_email }}</p>
                    @if ($appointment->notes)<p class="mt-1 max-w-xs text-xs italic text-subtle">{{ $appointment->notes }}</p>@endif
                </td>
                <td class="px-4 py-3">{{ $appointment->service->name }}<p class="text-xs text-subtle">with {{ $appointment->stylist->name }}{{ $appointment->occasion ? ' · '.ucfirst($appointment->occasion) : '' }}</p></td>
                <td class="px-4 py-3 text-xs text-muted">{{ $appointment->appointment_date->toFormattedDateString() }}<p>{{ $appointment->time_range }}</p></td>
                <td class="px-4 py-3">{{ (float) $appointment->deposit_total > 0 ? $salon->currency.' '.number_format((float) $appointment->deposit_total, 0, ',', '.') : 'None' }}</td>
                <td class="px-4 py-3">
                    <x-ui.badge :tone="match ($appointment->status) { 'pending' => 'warning', 'confirmed' => 'success', default => 'neutral' }">{{ ucfirst($appointment->status) }}</x-ui.badge>
                </td>
                <td class="px-4 py-3 text-right">
                    @if ($appointment->status === 'pending')
                        <form method="POST" action="{{ route('admin.appointments.status', $appointment) }}" class="inline">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="confirmed">
                            <button type="submit" class="text-success hover:underline">Confirm</button>
                        </form>
                        <x-ui.confirm-form :action="route('admin.appointments.status', $appointment)" method="PATCH" message="Cancel this appointment?" confirm-label="Cancel appointment" cancel-label="Keep" trigger-class="ml-3 text-danger hover:underline">
                            <x-slot:fields>
                                <input type="hidden" name="status" value="cancelled">
                            </x-slot:fields>
                            Cancel
                        </x-ui.confirm-form>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-ui.table>

    <div class="mt-4">{{ $appointments->links() }}</div>
</x-layouts.admin>
