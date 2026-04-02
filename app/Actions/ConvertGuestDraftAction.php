<?php

// app/Actions/ConvertGuestDraftAction.php

declare(strict_types=1);

namespace App\Actions;

use App\Models\GuestDraft;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConvertGuestDraftAction
{
    /**
     * Convert an existing guest draft into a real Invitation owned by $user.
     *
     * Returns the created Invitation, or null when no valid draft is found.
     */
    public function execute(User $user, string $guestSessionId): ?Invitation
    {
        $draft = GuestDraft::bySession($guestSessionId)->notExpired()->first();

        if (! $draft) {
            return null;
        }

        return DB::transaction(function () use ($draft, $user): Invitation {
            $data = $draft->data ?? [];

            $invitation = Invitation::create([
                'user_id'       => $user->id,
                'template_id'   => $draft->template_id,
                'slug'          => $this->generateUniqueSlug($data['title'] ?? null),
                'title'         => $data['title'] ?? 'Undangan Saya',
                'event_type'    => $data['event_type'] ?? 'pernikahan',
                'custom_config' => $data['custom_config'] ?? null,
                'status'        => 'draft',
            ]);

            if (! empty($data['details']) && is_array($data['details'])) {
                $invitation->details()->create(
                    array_intersect_key($data['details'], array_flip([
                        'groom_name', 'bride_name', 'groom_parent_names', 'bride_parent_names',
                        'groom_photo_url', 'bride_photo_url', 'birthday_person_name',
                        'birthday_photo_url', 'birthday_age', 'opening_text', 'closing_text',
                        'cover_photo_url',
                    ]))
                );
            }

            if (! empty($data['events']) && is_array($data['events'])) {
                foreach ($data['events'] as $i => $event) {
                    $invitation->events()->create(array_merge(
                        array_intersect_key($event, array_flip([
                            'event_name', 'event_date', 'start_time', 'end_time',
                            'venue_name', 'venue_address', 'maps_url',
                        ])),
                        ['sort_order' => $i]
                    ));
                }
            }

            $draft->delete();

            Cookie::queue(Cookie::forget('guest_session_id'));

            return $invitation;
        });
    }

    private function generateUniqueSlug(?string $title): string
    {
        $base = Str::slug($title ?? 'undangan');

        do {
            $slug = $base . '-' . Str::lower(Str::random(6));
        } while (Invitation::withTrashed()->where('slug', $slug)->exists());

        return $slug;
    }
}
