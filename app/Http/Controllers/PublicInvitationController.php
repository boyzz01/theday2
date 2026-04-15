<?php

// app/Http/Controllers/PublicInvitationController.php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GuestMessage;
use App\Models\Invitation;
use App\Models\InvitationView;
use App\Models\Rsvp;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class PublicInvitationController extends Controller
{
    // ─── GET /{slug} ──────────────────────────────────────────────

    public function show(Request $request, string $slug): Response|RedirectResponse
    {
        $invitation = Invitation::where('slug', $slug)
            ->with([
                'details',
                'events'    => fn ($q) => $q->orderBy('sort_order')->orderBy('event_date'),
                'galleries' => fn ($q) => $q->orderBy('sort_order'),
                'music'     => fn ($q) => $q->where('is_default', true)->limit(1),
                'sections',
                'template:id,name,slug,default_config',
            ])
            ->firstOrFail();

        // ── Gate checks ───────────────────────────────────────────
        if (! $invitation->isPublished()) {
            abort(404);
        }

        // ── Password gate ─────────────────────────────────────────
        $sessionKey   = "inv_unlocked_{$invitation->id}";
        $needPassword = $invitation->is_password_protected
            && ! $request->session()->get($sessionKey);

        // ── Track view (once per session) ─────────────────────────
        if (! $needPassword) {
            $this->trackView($request, $invitation);
        }

        // ── Build merged config ───────────────────────────────────
        $config = array_merge(
            $invitation->template->default_config ?? [],
            $invitation->custom_config             ?? []
        );

        // ── Load approved messages ────────────────────────────────
        $messages = $invitation->guestMessages()
            ->approved()
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($m) => [
                'id'         => $m->id,
                'name'       => $m->name,
                'message'    => $m->message,
                'created_at' => $m->created_at->diffForHumans(),
            ]);

        return Inertia::render('Invitation/Show', [
            'invitation' => [
                'id'        => $invitation->id,
                'title'     => $invitation->title,
                'slug'      => $invitation->slug,
                'event_type' => $invitation->event_type->value,
                'details'    => $invitation->details ? [
                    'groom_name'           => $invitation->details->groom_name,
                    'groom_nickname'       => $invitation->details->groom_nickname,
                    'bride_name'           => $invitation->details->bride_name,
                    'bride_nickname'       => $invitation->details->bride_nickname,
                    'groom_parent_names'   => $invitation->details->groom_parent_names,
                    'bride_parent_names'   => $invitation->details->bride_parent_names,
                    'groom_photo_url'      => $invitation->details->groom_photo_url,
                    'bride_photo_url'      => $invitation->details->bride_photo_url,
                    'opening_text'         => $invitation->details->opening_text,
                    'closing_text'         => $invitation->details->closing_text,
                    'cover_photo_url'      => $invitation->details->cover_photo_url,
                ] : null,
                'events'    => $invitation->events->map(fn ($e) => [
                    'id'            => $e->id,
                    'event_name'    => $e->event_name,
                    'event_date'    => $e->event_date?->format('Y-m-d'),
                    'event_date_formatted' => $e->event_date
                        ? Carbon::parse($e->event_date)
                            ->locale('id')
                            ->translatedFormat('l, d F Y')
                        : null,
                    'start_time'    => $e->start_time ? substr($e->start_time, 0, 5) : null,
                    'end_time'      => $e->end_time   ? substr($e->end_time, 0, 5)   : null,
                    'venue_name'    => $e->venue_name,
                    'venue_address' => $e->venue_address,
                    'maps_url'      => $e->maps_url,
                ])->values(),
                'galleries' => $invitation->galleries->map(fn ($g) => [
                    'id'        => $g->id,
                    'image_url' => $g->image_url,
                    'caption'   => $g->caption,
                ])->values(),
                'music' => $invitation->music->first() ? [
                    'title'    => $invitation->music->first()->title,
                    'file_url' => $invitation->music->first()->file_url,
                ] : null,
                'config'        => $config,
                'template_slug' => $invitation->template?->slug,
                'expires_at'    => $invitation->expires_at?->toIso8601String(),
                // section_key → { enabled, data } map (null = no sections yet → show all)
                'sections'      => $invitation->sections->isNotEmpty()
                    ? $invitation->sections
                        ->mapWithKeys(fn ($s) => [
                            $s->section_key => [
                                'enabled' => $s->is_required ? true : (bool) $s->is_enabled,
                                'data'    => $s->data_json ?? [],
                            ],
                        ])
                        ->toArray()
                    : null,
            ],
            'messages'     => $messages,
            'needPassword' => $needPassword,
        ]);
    }

    // ─── POST /{slug}/unlock ──────────────────────────────────────

    public function unlock(Request $request, string $slug): JsonResponse
    {
        $invitation = Invitation::where('slug', $slug)->firstOrFail();

        if (! $invitation->is_password_protected) {
            return response()->json(['success' => true]);
        }

        $password = $request->input('password', '');

        if (! Hash::check($password, $invitation->password)) {
            return response()->json(['message' => 'Password salah.'], 422);
        }

        $request->session()->put("inv_unlocked_{$invitation->id}", true);

        // Track view now that they've unlocked
        $this->trackView($request, $invitation);

        return response()->json(['success' => true]);
    }

    // ─── POST /{slug}/rsvp ────────────────────────────────────────

    public function rsvp(Request $request, string $slug): JsonResponse
    {
        $invitation = Invitation::where('slug', $slug)->firstOrFail();

        if (! $invitation->isPublished()) {
            return response()->json(['message' => 'Undangan tidak tersedia.'], 404);
        }

        $data = $request->validate([
            'guest_name'  => 'required|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'attendance'  => 'required|in:hadir,tidak_hadir,ragu',
            'guest_count' => 'nullable|integer|min:1|max:20',
            'notes'       => 'nullable|string|max:500',
        ], [
            'guest_name.required' => 'Nama tamu wajib diisi.',
            'attendance.required' => 'Kehadiran wajib dipilih.',
            'guest_count.max'     => 'Jumlah tamu maksimal 20 orang.',
        ]);

        $rsvp = Rsvp::create([
            'invitation_id' => $invitation->id,
            'guest_name'    => $data['guest_name'],
            'phone'         => $data['phone'] ?? null,
            'attendance'    => $data['attendance'],
            'guest_count'   => $data['guest_count'] ?? 1,
            'notes'         => $data['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'RSVP berhasil dikirim. Terima kasih!',
            'data'    => [
                'id'          => $rsvp->id,
                'guest_name'  => $rsvp->guest_name,
                'attendance'  => $rsvp->attendance->value,
                'guest_count' => $rsvp->guest_count,
            ],
        ], 201);
    }

    // ─── POST /{slug}/messages ────────────────────────────────────

    public function storeMessage(Request $request, string $slug): JsonResponse
    {
        $invitation = Invitation::where('slug', $slug)->firstOrFail();

        if (! $invitation->isPublished()) {
            return response()->json(['message' => 'Undangan tidak tersedia.'], 404);
        }

        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'message' => 'required|string|min:2|max:1000',
        ], [
            'name.required'    => 'Nama wajib diisi.',
            'message.required' => 'Ucapan wajib diisi.',
            'message.min'      => 'Ucapan minimal 2 karakter.',
            'message.max'      => 'Ucapan maksimal 1000 karakter.',
        ]);

        $msg = GuestMessage::create([
            'invitation_id' => $invitation->id,
            'name'          => $data['name'],
            'message'       => $data['message'],
            'is_approved'   => true,
        ]);

        return response()->json([
            'message' => 'Ucapan berhasil dikirim.',
            'data'    => [
                'id'         => $msg->id,
                'name'       => $msg->name,
                'message'    => $msg->message,
                'created_at' => $msg->created_at->diffForHumans(),
            ],
        ], 201);
    }

    // ─── GET /{slug}/messages ─────────────────────────────────────

    public function messages(string $slug): JsonResponse
    {
        $invitation = Invitation::where('slug', $slug)->firstOrFail();

        $messages = $invitation->guestMessages()
            ->approved()
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn ($m) => [
                'id'         => $m->id,
                'name'       => $m->name,
                'message'    => $m->message,
                'created_at' => $m->created_at->diffForHumans(),
            ]);

        return response()->json(['data' => $messages]);
    }

    // ─── Private helpers ──────────────────────────────────────────

    private function trackView(Request $request, Invitation $invitation): void
    {
        $sessionViewKey = "inv_viewed_{$invitation->id}";

        // Only track once per session
        if ($request->session()->get($sessionViewKey)) {
            return;
        }

        $request->session()->put($sessionViewKey, true);

        InvitationView::create([
            'invitation_id' => $invitation->id,
            'ip_address'    => $request->ip(),
            'user_agent'    => $request->userAgent(),
            'referrer'      => $request->header('referer'),
            'viewed_at'     => now(),
        ]);

        $invitation->increment('view_count');
    }
}
