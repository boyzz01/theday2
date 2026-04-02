<?php

// app/Policies/InvitationPolicy.php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Models\User;

class InvitationPolicy
{
    /**
     * Admins bypass all checks.
     */
    public function before(User $user): ?bool
    {
        if ($user->role?->value === 'admin') {
            return true;
        }

        return null; // fall through to individual methods
    }

    /**
     * Any authenticated user can create invitations.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the owner can view an invitation (editor context).
     */
    public function view(User $user, Invitation $invitation): bool
    {
        return $invitation->user_id === $user->id;
    }

    /**
     * Only the owner can update an invitation.
     */
    public function update(User $user, Invitation $invitation): bool
    {
        return $invitation->user_id === $user->id;
    }

    /**
     * Only the owner can delete.
     */
    public function delete(User $user, Invitation $invitation): bool
    {
        return $invitation->user_id === $user->id;
    }

    /**
     * Owner can publish only if the invitation is in draft/expired state.
     */
    public function publish(User $user, Invitation $invitation): bool
    {
        return $invitation->user_id === $user->id
            && $invitation->status !== InvitationStatus::Published;
    }

    /**
     * Owner can unpublish only if currently published.
     */
    public function unpublish(User $user, Invitation $invitation): bool
    {
        return $invitation->user_id === $user->id
            && $invitation->status === InvitationStatus::Published;
    }
}
