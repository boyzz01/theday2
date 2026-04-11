<?php

// app/Actions/CreateInvitationFromTemplateAction.php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Invitation;
use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Str;

class CreateInvitationFromTemplateAction
{
    public function execute(User $user, string $templateId): ?Invitation
    {
        $template = Template::find($templateId);

        if (! $template) {
            return null;
        }

        // Reuse existing draft if one exists (max 1 draft per user)
        $invitation = Invitation::where('user_id', $user->id)
            ->where('status', 'draft')
            ->first();

        if ($invitation) {
            $invitation->update([
                'template_id' => $template->id,
                'title'       => '',
                'event_type'  => 'pernikahan',
            ]);
            $invitation->details()->updateOrCreate(
                ['invitation_id' => $invitation->id],
                []
            );
        } else {
            $invitation = Invitation::create([
                'user_id'     => $user->id,
                'template_id' => $template->id,
                'title'       => '',
                'event_type'  => 'pernikahan',
                'slug'        => $this->generateUniqueSlug(),
                'status'      => 'draft',
            ]);
            $invitation->details()->create(['invitation_id' => $invitation->id]);
        }

        return $invitation;
    }

    private function generateUniqueSlug(): string
    {
        do {
            $slug = Str::random(8);
        } while (Invitation::withTrashed()->where('slug', $slug)->exists());

        return $slug;
    }
}
