<?php

namespace App\Modules\Operations\Queries;

use App\Models\Appointment;
use App\Models\HandoverRequest;
use App\Models\Salon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * What the salon team sees first when they sign in: counts of what they manage
 * and the few things waiting for them.
 */
class DashboardMetrics
{
    /**
     * @return array{services: int, stylists: int, knowledge_items: int, pending_appointments: int, open_handovers: int}
     */
    public function counts(Salon $salon): array
    {
        return [
            'services' => $salon->services()->count(),
            'stylists' => $salon->stylists()->count(),
            'knowledge_items' => $salon->knowledgeItems()->count(),
            'pending_appointments' => $salon->appointments()->where('status', Appointment::STATUS_PENDING)->count(),
            'open_handovers' => $this->openHandovers($salon)->count(),
        ];
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function recentAppointments(Salon $salon, int $limit = 5): Collection
    {
        return $salon->appointments()->latest()->take($limit)->with(['service', 'stylist'])->get();
    }

    /**
     * @return Collection<int, HandoverRequest>
     */
    public function latestOpenHandovers(Salon $salon, int $limit = 5): Collection
    {
        return $this->openHandovers($salon)->latest()->take($limit)->with('conversation')->get();
    }

    /**
     * @return Builder<HandoverRequest>
     */
    private function openHandovers(Salon $salon): Builder
    {
        return HandoverRequest::whereHas('conversation', fn ($q) => $q->where('salon_id', $salon->id))
            ->where('status', HandoverRequest::STATUS_OPEN);
    }
}
