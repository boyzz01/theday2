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

        // Reload + recalculate completion statuses
        $invitation->load('sections');

        foreach ($invitation->sections as $section) {
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
        $d = $inv->details;
        return [
            'headline'      => 'The Wedding Of',
            'groom_name'    => $d?->groom_name ?? '',
            'bride_name'    => $d?->bride_name ?? '',
            'cover_image'   => $d?->cover_photo_url ? ['url' => $d->cover_photo_url] : null,
            'opening_text'  => $d?->opening_text ?? 'Kepada Yth.\nBapak/Ibu/Saudara/i',
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
        if (empty($d['groom_name']) && empty($d['bride_name'])) return 'incomplete';
        if (empty($d['groom_name']) || empty($d['bride_name']))  return 'incomplete';
        return 'complete';
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
