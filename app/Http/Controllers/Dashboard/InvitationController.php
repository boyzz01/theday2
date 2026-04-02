<?php

// app/Http/Controllers/Dashboard/InvitationController.php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    // ─── Pages ───────────────────────────────────────────────────────

    public function create(Request $request): Response
    {
        $template = Template::active()
            ->with('category:id,name,slug')
            ->findOrFail($request->get('template'));

        $defaultMusic = [
            ['id' => 'canon-d',          'title' => 'Canon in D — Pachelbel',              'file_url' => null],
            ['id' => 'thousand-years',   'title' => 'A Thousand Years — Christina Perri',  'file_url' => null],
            ['id' => 'perfect',          'title' => 'Perfect — Ed Sheeran',                'file_url' => null],
            ['id' => 'cant-help',        'title' => "Can't Help Falling in Love — Elvis",  'file_url' => null],
            ['id' => 'all-of-me',        'title' => 'All of Me — John Legend',             'file_url' => null],
            ['id' => 'marry-you',        'title' => 'Marry You — Bruno Mars',              'file_url' => null],
            ['id' => 'thinking-out-loud','title' => 'Thinking Out Loud — Ed Sheeran',      'file_url' => null],
        ];

        $fonts = [
            ['value' => 'Playfair Display',   'label' => 'Playfair Display',   'category' => 'Serif'],
            ['value' => 'Cormorant Garamond', 'label' => 'Cormorant Garamond', 'category' => 'Serif'],
            ['value' => 'Great Vibes',        'label' => 'Great Vibes',        'category' => 'Script'],
            ['value' => 'Dancing Script',     'label' => 'Dancing Script',     'category' => 'Script'],
            ['value' => 'Parisienne',         'label' => 'Parisienne',         'category' => 'Script'],
            ['value' => 'Cinzel',             'label' => 'Cinzel',             'category' => 'Display'],
            ['value' => 'Lato',               'label' => 'Lato',               'category' => 'Sans-serif'],
            ['value' => 'Montserrat',         'label' => 'Montserrat',         'category' => 'Sans-serif'],
        ];

        return Inertia::render('Dashboard/Invitations/Create', [
            'template' => [
                'id'             => $template->id,
                'name'           => $template->name,
                'slug'           => $template->slug,
                'thumbnail_url'  => $template->thumbnail_url,
                'tier'           => $template->tier->value,
                'default_config' => $template->default_config ?? [],
                'category'       => [
                    'name' => $template->category->name,
                    'slug' => $template->category->slug,
                ],
            ],
            'defaultMusic' => $defaultMusic,
            'fonts'        => $fonts,
        ]);
    }

    // ─── API – Invitation ─────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'template_id' => 'required|uuid|exists:templates,id',
            'title'       => 'required|string|max:255',
            'event_type'  => 'required|in:pernikahan,ulang_tahun',
        ]);

        $invitation = Invitation::create([
            'user_id'     => Auth::id(),
            'template_id' => $data['template_id'],
            'title'       => $data['title'],
            'event_type'  => $data['event_type'],
            'slug'        => $this->generateUniqueSlug($data['title']),
            'status'      => 'draft',
        ]);

        // Bootstrap empty details row so subsequent PATCH always hits updateOrCreate
        $invitation->details()->create(['invitation_id' => $invitation->id]);

        return response()->json([
            'id'   => $invitation->id,
            'slug' => $invitation->slug,
        ], 201);
    }

    public function update(Request $request, Invitation $invitation): JsonResponse
    {
        $this->authorizeOwner($invitation);

        $data = $request->validate([
            'title'      => 'sometimes|string|max:255',
            'event_type' => 'sometimes|in:pernikahan,ulang_tahun',
            'slug'       => 'sometimes|string|max:100|alpha_dash|unique:invitations,slug,' . $invitation->id,
        ]);

        $invitation->update($data);

        return response()->json(['success' => true]);
    }

    // ─── API – Details ────────────────────────────────────────────────

    public function updateDetails(Request $request, Invitation $invitation): JsonResponse
    {
        $this->authorizeOwner($invitation);

        $data = $request->validate([
            'groom_name'           => 'nullable|string|max:255',
            'bride_name'           => 'nullable|string|max:255',
            'groom_parent_names'   => 'nullable|string|max:255',
            'bride_parent_names'   => 'nullable|string|max:255',
            'groom_photo_url'      => 'nullable|string|max:2048',
            'bride_photo_url'      => 'nullable|string|max:2048',
            'birthday_person_name' => 'nullable|string|max:255',
            'birthday_age'         => 'nullable|integer|min:1|max:200',
            'birthday_photo_url'   => 'nullable|string|max:2048',
            'opening_text'         => 'nullable|string|max:2000',
            'closing_text'         => 'nullable|string|max:2000',
            'cover_photo_url'      => 'nullable|string|max:2048',
        ]);

        $invitation->details()->updateOrCreate(
            ['invitation_id' => $invitation->id],
            $data
        );

        return response()->json(['success' => true]);
    }

    // ─── API – Events ─────────────────────────────────────────────────

    public function syncEvents(Request $request, Invitation $invitation): JsonResponse
    {
        $this->authorizeOwner($invitation);

        $data = $request->validate([
            'events'                 => 'present|array|max:20',
            'events.*.event_name'    => 'required|string|max:255',
            'events.*.event_date'    => 'required|date_format:Y-m-d',
            'events.*.start_time'    => 'nullable|date_format:H:i',
            'events.*.end_time'      => 'nullable|date_format:H:i',
            'events.*.venue_name'    => 'required|string|max:255',
            'events.*.venue_address' => 'nullable|string|max:1000',
            'events.*.maps_url'      => 'nullable|url|max:2048',
            'events.*.sort_order'    => 'sometimes|integer|min:0',
        ]);

        $invitation->events()->delete();

        foreach ($data['events'] as $i => $eventData) {
            $invitation->events()->create([
                ...$eventData,
                'sort_order' => $eventData['sort_order'] ?? $i,
            ]);
        }

        $synced = $invitation->events()->get()->map(fn ($e) => [
            'id'            => $e->id,
            'event_name'    => $e->event_name,
            'event_date'    => $e->event_date?->format('Y-m-d'),
            'start_time'    => $e->start_time,
            'end_time'      => $e->end_time,
            'venue_name'    => $e->venue_name,
            'venue_address' => $e->venue_address,
            'maps_url'      => $e->maps_url,
            'sort_order'    => $e->sort_order,
        ])->values();

        return response()->json(['events' => $synced]);
    }

    // ─── API – Gallery ────────────────────────────────────────────────

    public function syncGallery(Request $request, Invitation $invitation): JsonResponse
    {
        $this->authorizeOwner($invitation);

        $data = $request->validate([
            'galleries'              => 'present|array|max:50',
            'galleries.*.image_url'  => 'required|string|max:2048',
            'galleries.*.caption'    => 'nullable|string|max:255',
            'galleries.*.sort_order' => 'sometimes|integer|min:0',
        ]);

        $invitation->galleries()->delete();

        foreach ($data['galleries'] as $i => $item) {
            $invitation->galleries()->create([
                ...$item,
                'sort_order' => $item['sort_order'] ?? $i,
            ]);
        }

        return response()->json(['success' => true]);
    }

    // ─── API – Music ──────────────────────────────────────────────────

    public function syncMusic(Request $request, Invitation $invitation): JsonResponse
    {
        $this->authorizeOwner($invitation);

        $data = $request->validate([
            'music'                => 'present|array|max:10',
            'music.*.title'        => 'required|string|max:255',
            'music.*.file_url'     => 'required|string|max:2048',
            'music.*.is_default'   => 'sometimes|boolean',
            'music.*.sort_order'   => 'sometimes|integer|min:0',
        ]);

        $invitation->music()->delete();

        foreach ($data['music'] as $i => $item) {
            $invitation->music()->create([
                ...$item,
                'sort_order' => $item['sort_order'] ?? $i,
                'is_default' => $item['is_default'] ?? ($i === 0),
            ]);
        }

        return response()->json(['success' => true]);
    }

    // ─── API – Custom Config ──────────────────────────────────────────

    public function updateConfig(Request $request, Invitation $invitation): JsonResponse
    {
        $this->authorizeOwner($invitation);

        $data = $request->validate([
            'custom_config'               => 'required|array',
            'custom_config.primary_color' => 'nullable|string|max:20',
            'custom_config.font'          => 'nullable|string|max:100',
        ]);

        $existing = $invitation->custom_config ?? [];
        $invitation->update([
            'custom_config' => array_merge($existing, $data['custom_config']),
        ]);

        return response()->json(['success' => true]);
    }

    // ─── API – Publish ────────────────────────────────────────────────

    public function publish(Request $request, Invitation $invitation): JsonResponse
    {
        $this->authorizeOwner($invitation);

        $data = $request->validate([
            'action'                => 'required|in:draft,publish',
            'slug'                  => 'sometimes|string|max:100|alpha_dash|unique:invitations,slug,' . $invitation->id,
            'is_password_protected' => 'sometimes|boolean',
            'password'              => 'nullable|string|min:4|max:50',
            'expires_at'            => 'nullable|date|after:now',
        ]);

        $updateData = [];

        if (isset($data['slug'])) {
            $updateData['slug'] = $data['slug'];
        }

        if (isset($data['is_password_protected'])) {
            $updateData['is_password_protected'] = $data['is_password_protected'];
        }

        if (!empty($data['password']) && ($data['is_password_protected'] ?? false)) {
            $updateData['password'] = bcrypt($data['password']);
        }

        if (array_key_exists('expires_at', $data)) {
            $updateData['expires_at'] = $data['expires_at'] ?: null;
        }

        if ($data['action'] === 'publish') {
            $updateData['status']       = 'published';
            $updateData['published_at'] = now();
        } else {
            $updateData['status'] = 'draft';
        }

        $invitation->update($updateData);
        $invitation->refresh();

        return response()->json([
            'success' => true,
            'status'  => $invitation->status->value,
            'slug'    => $invitation->slug,
        ]);
    }

    // ─── API – File Upload ────────────────────────────────────────────

    public function upload(Request $request, Invitation $invitation): JsonResponse
    {
        $this->authorizeOwner($invitation);

        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,gif|max:5120',
            'type' => 'required|in:photo,gallery,cover',
        ]);

        $path = $request->file('file')->store(
            "invitations/{$invitation->id}/{$request->type}",
            'public'
        );

        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    public function uploadAudio(Request $request, Invitation $invitation): JsonResponse
    {
        $this->authorizeOwner($invitation);

        $request->validate([
            'file' => 'required|file|mimes:mp3,wav,ogg,m4a|max:10240',
        ]);

        $path = $request->file('file')->store(
            "invitations/{$invitation->id}/music",
            'public'
        );

        return response()->json([
            'url'  => Storage::disk('public')->url($path),
            'name' => $request->file('file')->getClientOriginalName(),
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────

    private function authorizeOwner(Invitation $invitation): void
    {
        if ($invitation->user_id !== Auth::id()) {
            abort(403);
        }
    }

    private function generateUniqueSlug(string $title): string
    {
        $base  = Str::slug($title) ?: 'undangan';
        $slug  = $base;
        $count = 1;

        while (Invitation::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$count}";
            $count++;
        }

        return $slug;
    }
}
