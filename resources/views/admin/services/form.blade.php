@php($isEdit = $service->exists)

<x-layouts.admin :title="$isEdit ? 'Edit service' : 'Add service'">
    <form method="POST" action="{{ $isEdit ? route('admin.services.update', $service) : route('admin.services.store') }}" class="space-y-8">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <x-ui.card heading="Basics">
            <div class="mt-4 grid grid-cols-2 gap-4">
                <x-ui.select name="category" label="Category">
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected(old('category', $service->category) === $category)>{{ \Illuminate\Support\Str::headline($category) }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.input name="price" type="number" label="Price" step="5000" :value="old('price', $service->price)" required />
                <x-ui.input name="name" label="Name (Indonesian, default)" :value="old('name', $service->name)" required class="col-span-2" />
                <x-ui.textarea name="description" label="Description (Indonesian, default)" :value="old('description', $service->description)" class="col-span-2" />
                <x-ui.input name="translations[en][name]" label="Name (English)" :value="old('translations.en.name', $service->translations['en']['name'] ?? '')" />
                <x-ui.input name="translations[ja][name]" label="Name (Japanese)" :value="old('translations.ja.name', $service->translations['ja']['name'] ?? '')" />
                <x-ui.textarea name="translations[en][description]" label="Description (English)" :value="old('translations.en.description', $service->translations['en']['description'] ?? '')" />
                <x-ui.textarea name="translations[ja][description]" label="Description (Japanese)" :value="old('translations.ja.description', $service->translations['ja']['description'] ?? '')" />
            </div>
        </x-ui.card>

        <x-ui.card heading="Time & details">
            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <x-ui.input name="duration_minutes" type="number" label="Duration (minutes)" step="30" min="30" max="480" :value="old('duration_minutes', $service->duration_minutes)" required />
                <x-ui.input name="deposit" type="number" label="Deposit to hold the booking" step="10000" min="0" :value="old('deposit', $service->deposit ?? 0)" />
                <x-ui.input name="sort_order" type="number" label="Sort order" :value="old('sort_order', $service->sort_order)" />
            </div>
            <p class="mt-2 text-xs text-muted">Duration is rounded up to a whole 30 minutes, the unit a stylist's day is booked in.</p>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input name="tags" label="Tags (comma-separated)" :value="old('tags', implode(', ', $service->tags ?? []))" placeholder="halal, vegan, ammonia_free, sensitive_skin, for_men, bridal, signature" />
                <x-ui.input name="cautions" label="Cautions (comma-separated)" :value="old('cautions', implode(', ', $service->cautions ?? []))" placeholder="ammonia, fragrance, latex, nut_oil, acrylic, peroxide" />
                <x-ui.input name="image_url" label="Image path or URL" :value="old('image_url', $service->image_url)" placeholder="images/services/balayage-color.svg or https://…" class="sm:col-span-2" />
            </div>
            <div class="mt-4 flex flex-wrap gap-6">
                <x-ui.checkbox name="is_featured" label="Featured" :checked="old('is_featured', $service->is_featured)" />
                <x-ui.checkbox name="is_unavailable" label="Temporarily unavailable" :checked="old('is_unavailable', $service->is_unavailable)" />
                <x-ui.checkbox name="is_active" label="Published (visible on the website)" :checked="old('is_active', $service->is_active)" />
            </div>
        </x-ui.card>

        <div class="flex justify-end gap-3">
            <x-ui.link-button :href="route('admin.services.index')" variant="secondary">Cancel</x-ui.link-button>
            <x-ui.button type="submit">Save service</x-ui.button>
        </div>
    </form>
</x-layouts.admin>
