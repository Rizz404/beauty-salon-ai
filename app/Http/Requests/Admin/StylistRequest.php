<?php

namespace App\Http\Requests\Admin;

use App\Models\Stylist;
use App\Support\Text;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Create or update a stylist, with the portfolio photos added or removed in
 * the same form.
 */
class StylistRequest extends FormRequest
{
    /**
     * A record of another salon is "not found" before anything is validated.
     */
    public function authorize(): bool
    {
        $record = $this->route('stylist');

        if ($record) {
            Gate::authorize('update', $record);
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'title' => ['nullable', 'string', 'max:255'],
            'translations.en.name' => ['nullable', 'string'],
            'translations.en.title' => ['nullable', 'string'],
            'translations.en.description' => ['nullable', 'string'],
            'translations.ja.name' => ['nullable', 'string'],
            'translations.ja.title' => ['nullable', 'string'],
            'translations.ja.description' => ['nullable', 'string'],
            'specialty' => ['required', 'string', Rule::in(Stylist::specialties())],
            'years_experience' => ['required', 'integer', 'min:0', 'max:60'],
            'skills' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * The columns to write, without the slug (which only a new stylist gets).
     *
     * @return array<string, mixed>
     */
    public function stylistAttributes(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'translations' => [
                'en' => ['name' => $data['translations']['en']['name'] ?? null, 'title' => $data['translations']['en']['title'] ?? null, 'description' => $data['translations']['en']['description'] ?? null],
                'ja' => ['name' => $data['translations']['ja']['name'] ?? null, 'title' => $data['translations']['ja']['title'] ?? null, 'description' => $data['translations']['ja']['description'] ?? null],
            ],
            'specialty' => $data['specialty'],
            'years_experience' => $data['years_experience'],
            'skills' => Text::slugList($data['skills'] ?? null),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }

    /**
     * @return list<int>
     */
    public function imageIdsToDelete(): array
    {
        return collect($this->input('delete_images', []))->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @return list<array{url: string, alt?: ?string, tags?: ?string}>
     */
    public function newImages(): array
    {
        return collect($this->input('new_images', []))
            ->filter(fn ($row) => filled($row['url'] ?? null))
            ->values()
            ->all();
    }
}
