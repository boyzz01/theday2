<?php

declare(strict_types=1);

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\InvitationSection;
use App\Services\InvitationSectionSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EditorController extends Controller
{
    public function __construct(
        private readonly InvitationSectionSyncService $syncService,
    ) {}

    // ─── Pages ───────────────────────────────────────────────────────

    /**
     * Open the section editor for an invitation.
     * Route: GET /editor/invitations/{invitation}
     * Route: GET /editor/invitations/{invitation}/section/{sectionKey}
     */
    public function show(Request $request, Invitation $invitation, ?string $sectionKey = null): Response
    {
        Gate::authorize('update', $invitation);

        // Sync / initialise sections (idempotent)
        $sections = $this->syncService->syncForInvitation($invitation);

        // Resolve active section key
        $activeSectionKey = $sectionKey
            ?? $request->query('section')
            ?? $sections->where('is_enabled', true)->first()?->section_key
            ?? 'cover';

        // Ensure active key is valid
        if (! $sections->firstWhere('section_key', $activeSectionKey)) {
            $activeSectionKey = $sections->where('is_enabled', true)->first()?->section_key ?? 'cover';
        }

        $invitation->load(['template', 'details', 'events', 'galleries', 'music']);

        return Inertia::render('Editor/Show', [
            'invitation'       => $this->serializeInvitation($invitation),
            'sections'         => $sections->map(fn($s) => $this->serializeSection($s))->values(),
            'activeSectionKey' => $activeSectionKey,
        ]);
    }

    // ─── API ─────────────────────────────────────────────────────────

    /**
     * Save section data.
     * Route: PATCH /editor/invitations/{invitation}/sections/{section}
     */
    public function saveSection(Request $request, Invitation $invitation, InvitationSection $section): JsonResponse
    {
        Gate::authorize('update', $invitation);

        if ($section->invitation_id !== $invitation->id) {
            abort(403);
        }

        $validated = $request->validate([
            'data'       => 'sometimes|array',
            'style'      => 'sometimes|array',
            'is_enabled' => 'sometimes|boolean',
        ]);

        // Required sections cannot be disabled
        if (isset($validated['is_enabled']) && ! $validated['is_enabled'] && $section->is_required) {
            return response()->json(['message' => 'Section wajib tidak dapat dinonaktifkan.'], 422);
        }

        $section->fill(array_filter([
            'data_json'  => $validated['data']  ?? null,
            'style_json' => $validated['style'] ?? null,
            'is_enabled' => $validated['is_enabled'] ?? null,
        ], fn($v) => $v !== null));

        $section->completion_status = $this->syncService->calcCompletion($section);
        $section->last_validated_at = now();
        $section->save();

        $invitation->update(['last_edited_at' => now()]);

        return response()->json([
            'section'    => $this->serializeSection($section),
            'saved_at'   => now()->toISOString(),
        ]);
    }

    /**
     * Reorder sections.
     * Route: PATCH /editor/invitations/{invitation}/sections/reorder
     */
    public function reorderSections(Request $request, Invitation $invitation): JsonResponse
    {
        Gate::authorize('update', $invitation);

        $validated = $request->validate([
            'order'   => 'required|array',
            'order.*' => 'string',
        ]);

        InvitationSection::where('invitation_id', $invitation->id)
            ->get()
            ->each(function (InvitationSection $s) use ($validated) {
                $idx = array_search($s->section_key, $validated['order'], true);
                if ($idx !== false) {
                    $s->update(['sort_order' => $idx]);
                }
            });

        return response()->json(['reordered' => true]);
    }

    // ─── Serializers ─────────────────────────────────────────────────

    private function serializeInvitation(Invitation $inv): array
    {
        $cfg = $inv->custom_config ?? [];
        return [
            'id'            => $inv->id,
            'title'         => $inv->title,
            'slug'          => $inv->slug,
            'status'        => $inv->status->value,
            'published_at'  => $inv->published_at?->toISOString(),
            'last_edited_at' => $inv->last_edited_at?->toISOString(),
            'template'      => $inv->template ? [
                'id'   => $inv->template->id,
                'name' => $inv->template->name,
                'slug' => $inv->template->slug,
            ] : null,
            'config'        => $cfg,
            // Legacy data passed to live preview (InvitationRenderer compat)
            'details'       => $inv->details,
            'events'        => $inv->events,
            'galleries'     => $inv->galleries,
            'music'         => $inv->music->first(),
        ];
    }

    private function serializeSection(InvitationSection $s): array
    {
        return [
            'id'                => $s->id,
            'section_key'       => $s->section_key,
            'section_type'      => $s->section_type,
            'label'             => $s->label,
            'is_enabled'        => $s->is_enabled,
            'is_required'       => $s->is_required,
            'is_hidden'         => $s->is_hidden,
            'sort_order'        => $s->sort_order,
            'completion_status' => $s->completion_status,
            'validation_errors' => $s->validation_errors_json ?? [],
            'data'              => $s->data_json ?? [],
            'style'             => $s->style_json ?? [],
            'variant'           => $s->variant ? [
                'id'   => $s->variant->id,
                'code' => $s->variant->code,
                'name' => $s->variant->name,
            ] : null,
        ];
    }
}
