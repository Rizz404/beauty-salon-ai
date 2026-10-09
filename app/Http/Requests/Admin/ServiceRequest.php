<?php

namespace App\Http\Requests\Admin;

use App\Models\Service;
use App\Modules\Services\Enums\ServiceCategory;
use App\Support\Text;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Create or update a service. The route model binding and the policy decide
 * whose service it is; this decides what may be written.
 */
class ServiceRequest extends FormRequest
{
    /**
     * A record of another salon is "not found" before anything is validated.
     */
    public function authorize(): bool
    {
        $record = $this->route('service');

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
            'category' => ['required', 'string', Rule::in(ServiceCategory::values())],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'translations.en.name' => ['nullable', 'string'],
            'translations.en.description' => ['nullable', 'string'],
            'translations.ja.name' => ['nullable', 'string'],
            'translations.ja.description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:'.Service::BLOCK_MINUTES, 'max:480'],
            'deposit' => ['nullable', 'numeric', 'min:0'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'tags' => ['nullable', 'string'],
            'cautions' => ['nullable', 'string'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_unavailable' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * The columns to write, without the slug (which only a new service gets).
     * The duration is rounded up to a whole 30-minute block, the unit chair
     * time is booked in.
     *
     * @return array<string, mixed>
     */
    public function serviceAttributes(): array
    {
        $data = $this->validated();

        return [
            'category' => $data['category'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'translations' => [
                'en' => ['name' => $data['translations']['en']['name'] ?? null, 'description' => $data['translations']['en']['description'] ?? null],
                'ja' => ['name' => $data['translations']['ja']['name'] ?? null, 'description' => $data['translations']['ja']['description'] ?? null],
            ],
            'price' => $data['price'],
            'duration_minutes' => (int) (ceil($data['duration_minutes'] / Service::BLOCK_MINUTES) * Service::BLOCK_MINUTES),
            'deposit' => $data['deposit'] ?? 0,
            'image_url' => $data['image_url'] ?? null,
            'tags' => Text::slugList($data['tags'] ?? null),
            'cautions' => Text::slugList($data['cautions'] ?? null),
            'is_featured' => $this->boolean('is_featured'),
            'is_unavailable' => $this->boolean('is_unavailable'),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }
}
