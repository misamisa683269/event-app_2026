<?php

namespace App\Policies;

use App\Models\TicketType;
use App\Models\User;

class TicketTypePolicy
{
    /**
     * 券種を削除できるのは、そのイベントの主催者だけ。
     */
    public function delete(User $user, TicketType $ticketType): bool
    {
        return $ticketType->event->isOwnedBy($user);
    }
}
