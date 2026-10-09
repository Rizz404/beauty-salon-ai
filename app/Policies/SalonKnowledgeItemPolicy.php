<?php

namespace App\Policies;

use App\Models\SalonKnowledgeItem;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SalonKnowledgeItemPolicy extends SalonScopedPolicy
{
    public function update(User $user, SalonKnowledgeItem $knowledgeItem): Response
    {
        return $this->ownsSalon($user, $knowledgeItem->salon_id);
    }

    public function delete(User $user, SalonKnowledgeItem $knowledgeItem): Response
    {
        return $this->ownsSalon($user, $knowledgeItem->salon_id);
    }
}
