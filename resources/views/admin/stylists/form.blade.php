@php($isEdit = $stylist->exists)

<x-layouts.admin :title="$isEdit ? 'Edit stylist' : 'Add stylist'">
    <form method="POST" action="{{ $isEdit ? route('admin.stylists.update', $stylist) : route('admin.stylists.store') }}" class="space-y-8">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <x-ui.card heading="Basics">
            <div class="mt-4 grid grid-cols-2 gap-4">
                <x-ui.input name="name" label="Name" :value="old('name', $stylist->name)" required />
                <x-ui.input name="title" label="Title (Indonesian, default)" :value="old('title', $stylist->title)" placeholder="Senior colorist" />
                <x-ui.textarea name="description" label="Bio (Indonesian, default)" :value="old('description', $stylist->description)" class="col-span-2" />
                <x-ui.input name="translations[en][name]" label="Name (English)" :value="old('translations.en.name', $stylist->translations['en']['name'] ?? '')" />
                <x-ui.input name="translations[ja][name]" label="Name (Japanese)" :value="old('translations.ja.name', $stylist->translations['ja']['name'] ?? '')" />
                <x-ui.input name="translations[en][title]" label="Title (English)" :value="old('translations.en.title', $stylist->translations['en']['title'] ?? '')" />
                <x-ui.input name="translations[ja][title]" label="Title (Japanese)" :value="old('translations.ja.title', $stylist->translations['ja']['title'] ?? '')" />
                <x-ui.textarea name="translations[en][description]" label="Bio (English)" :value="old('translations.en.description', $stylist->translations['en']['description'] ?? '')" />
                <x-ui.textarea name="translations[ja][description]" label="Bio (Japanese)" :value="old('translations.ja.description', $stylist->translations['ja']['description'] ?? '')" />
            </div>
        </x-ui.card>

        <x-ui.card heading="Specialty">
            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <x-ui.select name="specialty" label="Specialty">
                    @foreach (\App\Models\Stylist::specialties() as $specialty)
                        <option value="{{ $specialty }}" @selected(old('specialty', $stylist->specialty) === $specialty)>{{ \Illuminate\Support\Str::headline($specialty) }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.input name="years_experience" type="number" label="Years of experience" min="0" max="60" :value="old('years_experience', $stylist->years_experience)" required />
                <x-ui.input name="sort_order" type="number" label="Sort order" :value="old('sort_order', $stylist->sort_order)" />
            </div>
            <p class="mt-2 text-xs text-muted">A stylist can be booked for every service in their specialty category.</p>
            <x-ui.input name="skills" label="Skills (comma-separated)" :value="old('skills', implode(', ', $stylist->skills ?? []))" placeholder="balayage, keratin, haircut, bridal_updo, gel_nails, nail_art" class="mt-4" />
            <x-ui.checkbox name="is_active" label="Published (visible on the website)" :checked="old('is_active', $stylist->is_active)" class="mt-4" />
        </x-ui.card>

        <x-ui.card heading="Portfolio">
            @if ($isEdit && $stylist->images->isNotEmpty())
                <div class="mt-4 space-y-2">
                    @foreach ($stylist->images as $image)
                        <div class="flex items-center gap-3 rounded-control border border-border p-2">
                            <img src="{{ $image->image_source }}" alt="{{ $image->alt_text }}" class="h-14 w-20 rounded-sm object-cover">
                            <div class="flex-1 text-xs text-muted">
                                <p class="truncate">{{ $image->image_url }}</p>
                                <p>Tags: {{ implode(', ', $image->tags ?? []) }}</p>
                            </div>
                            <label class="flex items-center gap-1 text-xs text-danger">
                                <input type="checkbox" name="delete_images[]" value="{{ $image->id }}" class="rounded-sm border-border-strong">
                                Delete
                            </label>
                        </div>
                    @endforeach
                </div>
            @endif

            <p class="mt-4 text-xs text-muted">Add photos by path under public/ or by URL. Tag them so the AI Beautician can pick the right one — e.g. "balayage, blonde" or "bridal, updo".</p>
            @for ($i = 0; $i < 3; $i++)
                <div class="mt-2 grid grid-cols-6 gap-2">
                    <input type="text" name="new_images[{{ $i }}][url]" aria-label="Photo {{ $i + 1 }} path or URL" placeholder="images/stylists/… or https://…" class="col-span-3 rounded-control border border-border-strong bg-surface px-3 py-2 text-sm text-text placeholder:text-subtle">
                    <input type="text" name="new_images[{{ $i }}][tags]" aria-label="Photo {{ $i + 1 }} tags" placeholder="balayage, blonde" class="col-span-2 rounded-control border border-border-strong bg-surface px-3 py-2 text-sm text-text placeholder:text-subtle">
                    <input type="text" name="new_images[{{ $i }}][alt]" aria-label="Photo {{ $i + 1 }} alt text" placeholder="Alt text" class="rounded-control border border-border-strong bg-surface px-3 py-2 text-sm text-text placeholder:text-subtle">
                </div>
            @endfor
        </x-ui.card>

        <div class="flex justify-end gap-3">
            <x-ui.link-button :href="route('admin.stylists.index')" variant="secondary">Cancel</x-ui.link-button>
            <x-ui.button type="submit">Save stylist</x-ui.button>
        </div>
    </form>
</x-layouts.admin>
