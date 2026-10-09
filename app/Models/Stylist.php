<?php

namespace App\Models;

use App\Modules\Services\Enums\ServiceCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A member of the salon's team. Their specialty is a service category, which
 * is how a service finds the stylists who can perform it.
 */
#[Fillable([
    'salon_id',
    'name',
    'slug',
    'title',
    'description',
    'translations',
    'specialty',
    'years_experience',
    'skills',
    'is_active',
    'sort_order',
])]
class Stylist extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @return list<string>
     */
    public static function specialties(): array
    {
        return ServiceCategory::values();
    }

    protected function casts(): array
    {
        return [
            'translations' => 'array',
            'skills' => 'array',
            'years_experience' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(StylistImage::class)->orderBy('sort_order');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(StylistSlot::class);
    }

    public function translatedName(string $locale): string
    {
        $value = $this->translations[$locale]['name'] ?? null;

        return filled($value) ? $value : $this->name;
    }

    public function translatedTitle(string $locale): ?string
    {
        $value = $this->translations[$locale]['title'] ?? null;

        return filled($value) ? $value : $this->title;
    }

    public function translatedDescription(string $locale): ?string
    {
        $value = $this->translations[$locale]['description'] ?? null;

        return filled($value) ? $value : $this->description;
    }
}
