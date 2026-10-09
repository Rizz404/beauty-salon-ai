<?php

namespace App\Modules\Services\Actions;

use App\Models\Salon;
use App\Models\Service;
use Illuminate\Support\Str;

/**
 * Creates a service or updates one. A new service gets a slug from its name; an
 * existing one keeps its slug so guest links never break.
 */
class SaveService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Salon $salon, array $attributes, ?Service $service = null): Service
    {
        if ($service) {
            $service->update($attributes);

            return $service;
        }

        return $salon->services()->create([...$attributes, 'slug' => $this->uniqueSlug($salon, $attributes['name'])]);
    }

    private function uniqueSlug(Salon $salon, string $name): string
    {
        $base = Str::slug($name) ?: 'service';
        $slug = $base;
        $suffix = 1;

        while ($salon->services()->withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
