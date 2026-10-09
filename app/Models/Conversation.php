<?php

namespace App\Models;

use App\Modules\Conversation\Enums\ConversationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'salon_id',
    'guest_token',
    'guest_name',
    'guest_email',
    'locale',
    'status',
    'handover_summary',
    'current_scene',
    'selected_service_id',
    'selected_stylist_id',
    'selected_facility_id',
    'appointment_state',
    'last_message_at',
])]
class Conversation extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = ConversationStatus::Active->value;

    public const STATUS_HANDED_OVER = ConversationStatus::HandedOver->value;

    public const STATUS_CLOSED = ConversationStatus::Closed->value;

    /**
     * The UI scenes the guest can be in while talking to the AI Beautician.
     */
    public const SCENES = ['home', 'services', 'service', 'stylists', 'stylist', 'facilities', 'facility_detail', 'appointment', 'handover'];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'appointment_state' => 'array',
        ];
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function selectedService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'selected_service_id');
    }

    public function selectedStylist(): BelongsTo
    {
        return $this->belongsTo(Stylist::class, 'selected_stylist_id');
    }

    public function selectedFacility(): BelongsTo
    {
        return $this->belongsTo(SalonKnowledgeItem::class, 'selected_facility_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class)->orderBy('created_at');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function handoverRequest(): HasOne
    {
        return $this->hasOne(HandoverRequest::class)->latestOfMany();
    }

    public function isHandedOver(): bool
    {
        return $this->status === self::STATUS_HANDED_OVER;
    }
}
