<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One 30-minute block of a stylist's day: the only source of truth for
 * availability. Stands in for a real salon-software adapter for V1: the AI
 * must reach this table (or its future adapter) through a tool call, never
 * answer from its own memory.
 */
#[Fillable([
    'stylist_id',
    'slot_date',
    'time_slot',
    'is_booked',
])]
class StylistSlot extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'slot_date' => 'date',
            'is_booked' => 'boolean',
        ];
    }

    public function stylist(): BelongsTo
    {
        return $this->belongsTo(Stylist::class);
    }

    public function isAvailable(): bool
    {
        return ! $this->is_booked;
    }
}
