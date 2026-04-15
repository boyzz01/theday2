<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invitation;
use App\Models\InvitationSection;
use App\Models\TemplateSection;
use Illuminate\Support\Collection;

/**
 * Initialises invitation_sections rows for an invitation.
 *
 * Phase-1 strategy:
 *  - On first editor open, create rows from template blueprint + backfill
 *    existing legacy data (invitation_details, invitation_events, invitation_galleries).
 *  - Subsequent opens are idempotent (only adds missing sections).
 */
class InvitationSectionSyncService
{
    /**
     * Ensure all template sections exist as invitation_section rows.
     * Returns the ordered section collection ready for the editor.
     */
    public function syncForInvitation(Invitation $invitation): Collection
    {
        $invitation->load(['template', 'details', 'events', 'galleries', 'sections']);

        $templateSections = TemplateSection::where('template_id', $invitation->template_id)
            ->orderBy('sort_order')
            ->get();

        foreach ($templateSections as $ts) {
            $existing = $invitation->sections
                ->firstWhere('section_key', $ts->section_key);

            if (! $existing) {
                InvitationSection::create([
                    'invitation_id'       => $invitation->id,
                    'template_section_id' => $ts->id,
                    'section_key'         => $ts->section_key,
                    'section_type'        => $ts->section_type,
                    'label'               => $ts->label,
                    'variant_id'          => $ts->default_variant_id,
                    'is_enabled'          => $ts->is_enabled_by_default,
                    'is_required'         => $ts->is_required,
                    'sort_order'          => $ts->sort_order,
                    'completion_status'   => 'empty',
                    'data_json'           => $this->backfillData($invitation, $ts->section_key, $ts->default_data_json),
                ]);
            }
        }

        // Reload + migrate legacy data + recalculate completion statuses
        $invitation->load('sections');

        foreach ($invitation->sections as $section) {
            // Migrate existing cover sections that still use legacy field names
            if ($section->section_type === 'cover') {
                $migrated = $this->migrateLegacyCoverData($section->data_json ?? []);
                if ($migrated !== null) {
                    $section->data_json = $migrated;
                    $section->save();
                }
            }

            $status = $this->calcCompletion($section);
            if ($section->completion_status !== $status) {
                $section->update(['completion_status' => $status]);
            }
        }

        $invitation->load('sections.variant');

        return $invitation->sections->sortBy('sort_order')->values();
    }

    // ─── Backfill from legacy tables ──────────────────────────────────

    private function backfillData(Invitation $invitation, string $key, ?array $defaults): ?array
    {
        return match ($key) {
            'envelope' => $this->backfillEnvelope($invitation),
            'cover'    => $this->backfillCover($invitation),
            'couple'   => $this->backfillCouple($invitation),
            'opening'  => $this->backfillOpening($invitation),
            'closing'  => $this->backfillClosing($invitation),
            'events'   => $this->backfillEvents($invitation),
            'gallery'  => $this->backfillGallery($invitation),
            default    => $defaults,
        };
    }

    private function backfillEnvelope(Invitation $inv): array
    {
        $d = $inv->details;
        return [
            'groom_name'     => $d?->groom_nickname ?: ($d?->groom_name ?? ''),
            'bride_name'     => $d?->bride_nickname ?: ($d?->bride_name ?? ''),
            'recipient_text' => $d?->opening_text ?? 'Kepada Yth. Bapak/Ibu/Saudara/i Tamu Undangan',
            'button_text'    => 'Buka Undangan',
            'bg_image_url'   => $d?->cover_photo_url ?? '',
        ];
    }

    private function backfillCover(Invitation $inv): array
    {
        $d     = $inv->details;
        $groom = $d?->groom_name ?? '';
        $bride = $d?->bride_name ?? '';

        // Build couple_names from legacy groom + bride fields
        $coupleNames = match (true) {
            ! empty($groom) && ! empty($bride) => "{$groom} & {$bride}",
            ! empty($groom)                    => $groom,
            ! empty($bride)                    => $bride,
            default                            => '',
        };

        return [
            'pretitle'               => 'The Wedding Of',
            'couple_names'           => $coupleNames,
            'event_date_text'        => '',
            'intro_text'             => $d?->opening_text ?? 'Kepada Bapak/Ibu/Saudara/i',
            'button_text'            => 'Buka Undangan',
            'guest_name_mode'        => 'query_param',
            'guest_name'             => null,
            'guest_query_key'        => 'to',
            'fallback_guest_text'    => 'Tamu Undangan',
            'show_guest_name'        => true,
            'background_image'       => $d?->cover_photo_url ? ['asset_id' => null, 'url' => $d->cover_photo_url] : null,
            'background_mobile_image' => null,
            'background_position'    => 'center',
            'background_size'        => 'cover',
            'text_align'             => 'center',
            'content_position'       => 'center',
            'overlay_opacity'        => 0.35,
            'show_ornament'          => true,
            'show_date'              => true,
            'show_pretitle'          => true,
            'music_on_open'          => true,
            'show_music_button'      => false,
            'open_action'            => 'enter_content',
        ];
    }

    /**
     * Detect old cover schema (groom_name/bride_name/headline) and upgrade to spec schema.
     * Returns migrated array, or null if already on new schema (no migration needed).
     */
    private function migrateLegacyCoverData(array $d): ?array
    {
        // Already on new schema — has couple_names key (even if empty)
        if (array_key_exists('couple_names', $d)) {
            return null;
        }

        // Old schema detected — transform
        $groom = trim($d['groom_name'] ?? '');
        $bride = trim($d['bride_name'] ?? '');

        $coupleNames = match (true) {
            $groom !== '' && $bride !== '' => "{$groom} & {$bride}",
            $groom !== ''                  => $groom,
            $bride !== ''                  => $bride,
            default                        => '',
        };

        // Migrate cover_image: could be { url } or null
        $bgImage = null;
        if (! empty($d['cover_image']['url'])) {
            $bgImage = ['asset_id' => null, 'url' => $d['cover_image']['url']];
        }

        return [
            'pretitle'               => $d['headline']     ?? 'The Wedding Of',
            'couple_names'           => $coupleNames,
            'event_date_text'        => '',
            'intro_text'             => $d['opening_text'] ?? 'Kepada Bapak/Ibu/Saudara/i',
            'button_text'            => 'Buka Undangan',
            'guest_name_mode'        => 'query_param',
            'guest_name'             => null,
            'guest_query_key'        => 'to',
            'fallback_guest_text'    => 'Tamu Undangan',
            'show_guest_name'        => true,
            'background_image'       => $bgImage,
            'background_mobile_image' => null,
            'background_position'    => 'center',
            'background_size'        => 'cover',
            'text_align'             => 'center',
            'content_position'       => 'center',
            'overlay_opacity'        => 0.35,
            'show_ornament'          => true,
            'show_date'              => true,
            'show_pretitle'          => true,
            'music_on_open'          => true,
            'show_music_button'      => false,
            'open_action'            => 'enter_content',
        ];
    }

    private function backfillCouple(Invitation $inv): array
    {
        $d = $inv->details;
        return [
            'groom' => [
                'full_name'    => $d?->groom_name ?? '',
                'nickname'     => $d?->groom_nickname ?? '',
                'father_name'  => '',
                'mother_name'  => '',
                'photo'        => $d?->groom_photo_url ? ['url' => $d->groom_photo_url] : null,
                'instagram'    => '',
            ],
            'bride' => [
                'full_name'    => $d?->bride_name ?? '',
                'nickname'     => $d?->bride_nickname ?? '',
                'father_name'  => '',
                'mother_name'  => '',
                'photo'        => $d?->bride_photo_url ? ['url' => $d->bride_photo_url] : null,
                'instagram'    => '',
            ],
        ];
    }

    private function backfillOpening(Invitation $inv): array
    {
        return [
            'title' => "Assalamu'alaikum Wr. Wb.",
            'body'  => $inv->details?->opening_text ?? '',
        ];
    }

    private function backfillClosing(Invitation $inv): array
    {
        return [
            'title'     => 'Terima Kasih',
            'body'      => $inv->details?->closing_text ?? '',
            'signature' => 'Kami yang berbahagia',
        ];
    }

    private function backfillEvents(Invitation $inv): array
    {
        $items = $inv->events->map(fn($e) => [
            'type'       => 'resepsi',
            'title'      => $e->event_name ?? '',
            'date'       => $e->event_date?->toDateString() ?? '',
            'start_time' => $e->start_time ?? '',
            'end_time'   => $e->end_time ?? '',
            'timezone'   => 'Asia/Jakarta',
            'venue_name' => $e->venue_name ?? '',
            'address'    => $e->venue_address ?? '',
            'maps_url'   => $e->maps_url ?? '',
            'note'       => null,
        ])->values()->all();

        return ['items' => $items];
    }

    private function backfillGallery(Invitation $inv): array
    {
        $items = $inv->galleries->map(fn($g, $i) => [
            'url'        => $g->image_url ?? '',
            'caption'    => $g->caption ?? '',
            'sort_order' => $g->sort_order ?? $i,
        ])->values()->all();

        return ['layout' => 'grid', 'items' => $items];
    }

    // ─── Completion status calculation ────────────────────────────────

    public function calcCompletion(InvitationSection $section): string
    {
        if (! $section->is_enabled) {
            return $section->completion_status === 'empty' ? 'empty' : $section->completion_status;
        }

        $data = $section->data_json ?? [];

        return match ($section->section_type) {
            'envelope' => $this->completeCover($data), // same logic: needs groom + bride name
            'cover'   => $this->completeCover($data),
            'couple'  => $this->completeCouple($data),
            'opening' => $this->completeText($data, 'body'),
            'closing' => $this->completeText($data, 'body'),
            'quote'   => $this->completeText($data, 'text'),
            'events'  => $this->completeItems($data),
            'gallery' => $this->completeGallery($data),
            'rsvp'    => isset($data['is_open']) ? 'complete' : 'incomplete',
            'wishes'  => 'complete', // always renderable
            'gift'    => $this->completeItems($data),
            'additional_info' => $this->completeItems($data),
            'love_story'      => $this->completeItems($data),
            default   => empty($data) ? 'empty' : 'complete',
        };
    }

    private function completeCover(array $d): string
    {
        // Support both new spec fields and legacy groom_name/bride_name
        $coupleNames = $d['couple_names']
            ?? (trim(($d['groom_name'] ?? '') . ' ' . ($d['bride_name'] ?? '')) ?: null);
        $buttonText  = $d['button_text'] ?? null;

        if (empty($coupleNames) && empty($buttonText)) return 'empty';
        if (empty($coupleNames) || empty($buttonText))  return 'incomplete';

        // Warn if visible optional fields are toggled on but empty
        $hasWarning = ($d['show_date']     ?? true)     && empty($d['event_date_text'])
                   || ($d['show_pretitle'] ?? true)     && empty($d['pretitle'])
                   || ($d['show_guest_name'] ?? true)   && ($d['guest_name_mode'] ?? 'query_param') === 'manual' && empty($d['guest_name']);

        return $hasWarning ? 'warning' : 'complete';
    }

    private function completeCouple(array $d): string
    {
        $groomName = $d['groom']['full_name'] ?? '';
        $brideName = $d['bride']['full_name'] ?? '';
        if (empty($groomName) || empty($brideName)) return 'incomplete';
        return 'complete';
    }

    private function completeText(array $d, string $field): string
    {
        return ! empty($d[$field]) ? 'complete' : 'empty';
    }

    private function completeItems(array $d): string
    {
        $items = $d['items'] ?? [];
        if (empty($items)) return 'empty';
        return 'complete';
    }

    private function completeGallery(array $d): string
    {
        $items = $d['items'] ?? [];
        if (empty($items)) return 'empty';
        if (count($items) === 0) return 'warning';
        return 'complete';
    }
}
