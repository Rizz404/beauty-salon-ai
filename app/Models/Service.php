<?php

namespace App\Models;

use App\Modules\Services\Enums\ServiceCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One treatment on the salon's service list. The price and duration here are
 * the only ones the AI Beautician may ever quote, and only after reading them
 * through a tool call.
 */
#[Fillable([
    'salon_id',
    'category',
    'name',
    'slug',
    'description',
    'translations',
    'price',
    'duration_minutes',
    'deposit',
    'image_url',
    'tags',
    'cautions',
    'is_featured',
    'is_unavailable',
    'is_active',
    'sort_order',
])]
class Service extends Model
{
    use HasFactory, SoftDeletes;

    public const CATEGORY_HAIR = ServiceCategory::Hair->value;

    public const CATEGORY_NAILS = ServiceCategory::Nails->value;

    public const CATEGORY_SKIN = ServiceCategory::Skin->value;

    public const CATEGORY_MAKEUP = ServiceCategory::Makeup->value;

    public const CATEGORY_SPA = ServiceCategory::Spa->value;

    /**
     * Tags a guest can filter on when they have a particular need.
     */
    public const NEED_TAGS = ['halal', 'vegan', 'ammonia_free', 'sensitive_skin', 'for_men', 'bridal'];

    /**
     * Chair time is booked in blocks of this many minutes.
     */
    public const BLOCK_MINUTES = 30;

    /**
     * @return list<string>
     */
    public static function categories(): array
    {
        return ServiceCategory::values();
    }

    protected function casts(): array
    {
        return [
            'translations' => 'array',
            'tags' => 'array',
            'cautions' => 'array',
            'price' => 'decimal:2',
            'deposit' => 'decimal:2',
            'duration_minutes' => 'integer',
            'is_featured' => 'boolean',
            'is_unavailable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * The picture to show, preferring a local photo for legacy service artwork.
     */
    public function getImageSourceAttribute(): ?string
    {
        if (blank($this->image_url)) {
            return null;
        }

        $imagePath = $this->image_url;

        if (preg_match('/\Aimages\/services\/[a-z0-9-]+\.svg\z/', $imagePath) === 1) {
            $photoPath = substr($imagePath, 0, -4).'.webp';

            if (is_file(public_path($photoPath))) {
                $imagePath = $photoPath;
            }
        }

        return str_starts_with($imagePath, 'http') ? $imagePath : asset($imagePath);
    }

    /**
     * How many 30-minute blocks of a stylist's day this service takes.
     */
    public function blockCount(): int
    {
        return max(1, (int) ceil($this->duration_minutes / self::BLOCK_MINUTES));
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? [], true);
    }

    public function translatedName(string $locale): string
    {
        $value = $this->translations[$locale]['name'] ?? null;

        return filled($value) ? $value : $this->name;
    }

    public function translatedDescription(string $locale): ?string
    {
        $value = $this->translations[$locale]['description'] ?? null;

        return filled($value) ? $value : $this->description;
    }
}
