<?php

// app/Actions/ChangeTemplateAction.php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Invitation;
use App\Models\InvitationSection;
use App\Models\Template;

class ChangeTemplateAction
{
    public function execute(Invitation $invitation, Template $newTemplate): void
    {
        // Update template_id
        $invitation->update(['template_id' => $newTemplate->id]);

        // All current templates support the same section keys (universal defaults).
        // Mark all existing sections as visible again in case they were hidden.
        // Re-run initializeForInvitation so any sections the new template needs exist.
        InvitationSection::where('invitation_id', $invitation->id)
            ->update(['is_visible_in_template' => true]);

        InvitationSection::initializeForInvitation($invitation->id);
    }
}
