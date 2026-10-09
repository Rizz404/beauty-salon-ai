<?php

namespace App\Models;

use App\Modules\Appointment\Enums\AppointmentStatus;
use App\Modules\Appointment\Enums\Occasion;
use App\Modules\Appointment\Support\StylistAvailability;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'reference',
    'salon_id',
    'service_id',
    'stylist_id',
    'conversation_id',
    'guest_name',
    'guest_email',
    'guest_phone',
    'contact_type',
    'appointment_date',
    'time_slot',
    'duration_minutes',
    'occasion',
    'deposit_total',
    'status',
    'notes',
])]
class Appointment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = AppointmentStatus::Pending->value;

    public const STATUS_CONFIRMED = AppointmentStatus::Confirmed->value;

    public const STATUS_CANCELLED = AppointmentStatus::Cancelled->value;

    /**
     * Occasions a guest can mention so the team can prepare for them.
     */
    public const OCCASIONS = [
        Occasion::Wedding->value,
        Occasion::Party->value,
        Occasion::Photoshoot->value,
        Occasion::Graduation->value,
        Occasion::Everyday->value,
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'duration_minutes' => 'integer',
            'deposit_total' => 'decimal:2',
        ];
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function stylist(): BelongsTo
    {
        return $this->belongsTo(Stylist::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * "11:00–12:30" for a 90-minute appointment that starts at 11:00.
     */
    public function getTimeRangeAttribute(): string
    {
        return StylistAvailability::range($this->time_slot, $this->duration_minutes);
    }

    /**
     * A short, unambiguous, non-sequential code guests can quote to staff.
     */
    public static function generateReference(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $reference = 'AP-'.collect(range(1, 6))
                ->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])
                ->implode('');
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }
}
