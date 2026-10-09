<x-layouts.admin title="Stylists">
    <x-slot:actions>
        <x-ui.link-button :href="route('admin.stylists.create')" size="sm">Add stylist</x-ui.link-button>
    </x-slot:actions>

    <x-ui.table :columns="['Name', 'Specialty', 'Experience', 'Portfolio', 'Status', '']" :empty="$stylists->isEmpty()" empty-text="No stylists yet.">
        @foreach ($stylists as $stylist)
            <tr>
                <td class="px-4 py-3 font-medium text-text">{{ $stylist->name }}@if ($stylist->title)<p class="text-xs font-normal text-subtle">{{ $stylist->title }}</p>@endif</td>
                <td class="px-4 py-3 text-muted">{{ \Illuminate\Support\Str::headline($stylist->specialty) }}</td>
                <td class="px-4 py-3">{{ $stylist->years_experience }} years</td>
                <td class="px-4 py-3">{{ $stylist->images_count }}</td>
                <td class="px-4 py-3">
                    <x-ui.badge :tone="$stylist->is_active ? 'success' : 'neutral'">{{ $stylist->is_active ? 'Active' : 'Hidden' }}</x-ui.badge>
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('admin.stylists.edit', $stylist) }}" class="text-muted hover:underline">Edit</a>
                    <x-ui.confirm-form :action="route('admin.stylists.destroy', $stylist)" message="Remove this stylist?" confirm-label="Remove" trigger-class="ml-3 text-danger hover:underline">Remove</x-ui.confirm-form>
                </td>
            </tr>
        @endforeach
    </x-ui.table>
</x-layouts.admin>
