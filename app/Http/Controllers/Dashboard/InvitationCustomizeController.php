<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Support\SectionAccess;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class InvitationCustomizeController extends Controller
{
    public function show(Request $request, Invitation $invitation): Response
    {
        abort_unless($invitation->user_id === $request->user()->id, 403);

        $invitation->load([
            'details',
            'events'    => fn ($q) => $q->orderBy('sort_order')->orderBy('event_date'),
            'galleries' => fn ($q) => $q->orderBy('sort_order'),
            'music'     => fn ($q) => $q->where('is_default', true)->limit(1),
            'template:id,name,slug,default_config',
        ]);

        $config = array_merge(
            $invitation->template->default_config ?? [],
            $invitation->custom_config             ?? []
        );

        return Inertia::render('Dashboard/Invitations/Customize', [
            'invitation'    => [
                'id'            => $invitation->id,
                'slug'          => $invitation->slug,
                'template_slug' => $invitation->template?->slug,
                'config'        => $config,
                'details'       => $invitation->details ? [
                    'groom_name'      => $invitation->details->groom_name,
                    'groom_nickname'  => $invitation->details->groom_nickname,
                    'bride_name'      => $invitation->details->bride_name,
                    'bride_nickname'  => $invitation->details->bride_nickname,
                    'opening_text'    => $invitation->details->opening_text,
                    'closing_text'    => $invitation->details->closing_text,
                    'cover_photo_url' => $invitation->details->cover_photo_url,
                ] : null,
                'events'    => $invitation->events->map(fn ($e) => [
                    'id'                   => $e->id,
                    'event_name'           => $e->event_name,
                    'event_date'           => $e->event_date?->format('Y-m-d'),
                    'event_date_formatted' => $e->event_date
                        ? Carbon::parse($e->event_date)->locale('id')->translatedFormat('l, d F Y')
                        : null,
                    'start_time'  => $e->start_time ? substr($e->start_time, 0, 5) : null,
                    'end_time'    => $e->end_time   ? substr($e->end_time, 0, 5)   : null,
                    'venue_name'  => $e->venue_name,
                    'maps_url'    => $e->maps_url,
                ])->values(),
                'galleries' => $invitation->galleries->map(fn ($g) => [
                    'id'        => $g->id,
                    'image_url' => $g->image_url,
                ])->values(),
                'music' => $invitation->music->first() ? [
                    'file_url' => $invitation->music->first()->file_url,
                ] : null,
                'sections' => null,
            ],
            'canUsePremium' => SectionAccess::isPremium($request->user()),
        ]);
    }

    public function update(Request $request, Invitation $invitation): JsonResponse
    {
        abort_unless($invitation->user_id === $request->user()->id, 403);

        if (! SectionAccess::isPremium($request->user())) {
            return response()->json(['error' => 'Fitur ini tersedia di paket Premium.'], 403);
        }

        $request->validate([
            'section_backgrounds'           => 'required|array',
            'section_backgrounds.*.type'    => 'nullable|in:image,video,color',
            'section_backgrounds.*.value'   => 'nullable|string|max:1000',
            'section_backgrounds.*.opacity' => 'nullable|numeric|min:0|max:1',
        ]);

        $config                        = $invitation->custom_config ?? [];
        $config['section_backgrounds'] = $request->input('section_backgrounds');

        $invitation->update(['custom_config' => $config]);

        return response()->json(['ok' => true]);
    }

    public function uploadBackground(Request $request, Invitation $invitation, string $key): JsonResponse
    {
        abort_unless($invitation->user_id === $request->user()->id, 403);

        if (! SectionAccess::isPremium($request->user())) {
            return response()->json(['error' => 'Fitur ini tersedia di paket Premium.'], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $path = $request->file('file')->store(
            "invitations/{$invitation->id}/sections/{$key}",
            'public'
        );

        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ]);
    }
}
