<x-layouts.admin title="Services">
    <x-slot:actions>
        <x-ui.link-button :href="route('admin.services.create')" size="sm">Add service</x-ui.link-button>
    </x-slot:actions>

    <x-ui.table :columns="['Name', 'Category', 'Duration', 'Price', 'Status', '']" :empty="$services->isEmpty()" empty-text="No services yet.">
        @foreach ($services as $service)
            <tr>
                <td class="px-4 py-3 font-medium text-text">{{ $service->name }}@if ($service->is_featured)<x-ui.badge tone="warning" class="ml-2">Featured</x-ui.badge>@endif</td>
                <td class="px-4 py-3 text-muted">{{ \Illuminate\Support\Str::headline($service->category) }}</td>
                <td class="px-4 py-3">{{ $service->duration_minutes }} min</td>
                <td class="px-4 py-3">{{ $salon->currency }} {{ number_format((float) $service->price, 0, ',', '.') }}@if ((float) $service->deposit > 0)<p class="text-xs text-subtle">Deposit {{ number_format((float) $service->deposit, 0, ',', '.') }}</p>@endif</td>
                <td class="px-4 py-3">
                    <x-ui.badge :tone="$service->is_active ? 'success' : 'neutral'">{{ $service->is_active ? 'Active' : 'Hidden' }}</x-ui.badge>
                    @if ($service->is_unavailable)
                        <x-ui.badge tone="danger" class="ml-1">Unavailable</x-ui.badge>
                    @endif
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('admin.services.edit', $service) }}" class="text-muted hover:underline">Edit</a>
                    <x-ui.confirm-form :action="route('admin.services.destroy', $service)" message="Delete this service?" confirm-label="Delete" trigger-class="ml-3 text-danger hover:underline">Delete</x-ui.confirm-form>
                </td>
            </tr>
        @endforeach
    </x-ui.table>
</x-layouts.admin>
