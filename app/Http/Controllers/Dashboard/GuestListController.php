<?php

// app/Http/Controllers/Dashboard/GuestListController.php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\GuestList;
use App\Models\Invitation;
use App\Models\WhatsAppMessageTemplate;
use App\Services\GuestSlugGenerator;
use App\Services\PhoneNumberNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class GuestListController extends Controller
{
    public function __construct(
        private readonly GuestSlugGenerator  $slugGenerator,
        private readonly PhoneNumberNormalizer $normalizer,
    ) {}

    // ─── Inertia Page ─────────────────────────────────────────────

    public function index(): Response
    {
        $userId = Auth::id();

        $invitations = Invitation::where('user_id', $userId)
            ->select('id', 'slug', 'title', 'status')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($i) => [
                'id'    => $i->id,
                'slug'  => $i->slug,
                'title' => $i->title,
                'status' => $i->status->value,
            ]);

        $hasTemplate = WhatsAppMessageTemplate::where('user_id', $userId)->exists();

        $defaultTemplate = WhatsAppMessageTemplate::where('user_id', $userId)
            ->where('is_default', true)
            ->first();

        return Inertia::render('Dashboard/GuestList/Index', [
            'invitations'     => $invitations,
            'hasTemplate'     => $hasTemplate,
            'defaultTemplate' => $defaultTemplate ? [
                'id'      => $defaultTemplate->id,
                'name'    => $defaultTemplate->name,
                'content' => $defaultTemplate->content,
            ] : null,
        ]);
    }

    // ─── API: Guest list with filters ─────────────────────────────

    public function guests(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $query = GuestList::with('invitation:id,slug,title')
            ->where('user_id', $userId);

        if ($search = $request->get('search')) {
            $query->search($search);
        }
        if ($status = $request->get('send_status')) {
            $query->filterSendStatus($status);
        }
        if ($rsvp = $request->get('rsvp_status')) {
            $query->filterRsvpStatus($rsvp);
        }
        if ($category = $request->get('category')) {
            $query->filterCategory($category);
        }
        if ($invId = $request->get('invitation_id')) {
            $query->filterInvitation($invId);
        }

        $query->sortBy($request->get('sort', 'newest'));

        $guests = $query->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => collect($guests->items())->map(fn ($g) => $this->guestResource($g)),
            'meta' => [
                'current_page' => $guests->currentPage(),
                'last_page'    => $guests->lastPage(),
                'total'        => $guests->total(),
                'per_page'     => $guests->perPage(),
            ],
        ]);
    }

    // ─── API: Summary ─────────────────────────────────────────────

    public function summary(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $base = GuestList::where('user_id', $userId);

        if ($invId = $request->get('invitation_id')) {
            $base->where('invitation_id', $invId);
        }

        $total = (clone $base)->count();

        return response()->json([
            'total'        => $total,
            'not_sent'     => (clone $base)->where('send_status', 'not_sent')->count(),
            'sent'         => (clone $base)->where('send_status', 'sent')->count(),
            'opened'       => (clone $base)->where('send_status', 'opened')->count(),
            'attending'    => (clone $base)->where('rsvp_status', 'attending')->count(),
            'not_attending'=> (clone $base)->where('rsvp_status', 'not_attending')->count(),
            'pending_rsvp' => (clone $base)->where('rsvp_status', 'pending')->count(),
        ]);
    }

    // ─── API: Categories ──────────────────────────────────────────

    public function categories(): JsonResponse
    {
        $categories = GuestList::where('user_id', Auth::id())
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return response()->json(['categories' => $categories]);
    }

    // ─── API: Store ───────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'invitation_id' => 'nullable|exists:invitations,id',
            'name'          => 'required|string|max:150',
            'phone_number'  => 'required|string|max:30',
            'category'      => 'nullable|string|max:50',
            'greeting'      => 'nullable|string|max:50',
            'note'          => 'nullable|string|max:500',
        ]);

        $userId = Auth::id();

        // Normalize phone
        if (! $this->normalizer->isValid($data['phone_number'])) {
            return response()->json([
                'errors' => ['phone_number' => ['Nomor WhatsApp tidak valid.']],
            ], 422);
        }

        $normalized = $this->normalizer->normalize($data['phone_number']);

        // Generate slug
        $slug = $this->slugGenerator->generate(
            $data['name'],
            $data['invitation_id'] ?? null,
            $userId
        );

        $guest = GuestList::create([
            ...$data,
            'user_id'          => $userId,
            'normalized_phone' => $normalized,
            'guest_slug'       => $slug,
        ]);

        $guest->load('invitation:id,slug,title');

        return response()->json($this->guestResource($guest), 201);
    }

    // ─── API: Update ──────────────────────────────────────────────

    public function update(Request $request, GuestList $guest): JsonResponse
    {
        $this->authorizeGuest($guest);

        $data = $request->validate([
            'invitation_id' => 'nullable|exists:invitations,id',
            'name'          => 'sometimes|string|max:150',
            'phone_number'  => 'sometimes|string|max:30',
            'category'      => 'nullable|string|max:50',
            'greeting'      => 'nullable|string|max:50',
            'note'          => 'nullable|string|max:500',
        ]);

        if (isset($data['phone_number'])) {
            if (! $this->normalizer->isValid($data['phone_number'])) {
                return response()->json([
                    'errors' => ['phone_number' => ['Nomor WhatsApp tidak valid.']],
                ], 422);
            }
            $data['normalized_phone'] = $this->normalizer->normalize($data['phone_number']);
        }

        $guest->update($data);
        $guest->load('invitation:id,slug,title');

        return response()->json($this->guestResource($guest->fresh()));
    }

    // ─── API: Destroy ─────────────────────────────────────────────

    public function destroy(GuestList $guest): JsonResponse
    {
        $this->authorizeGuest($guest);
        $guest->delete();

        return response()->json(['ok' => true]);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    private function authorizeGuest(GuestList $guest): void
    {
        if ($guest->user_id !== Auth::id()) {
            abort(403);
        }
    }

    public function guestResource(GuestList $guest): array
    {
        return [
            'id'             => $guest->id,
            'name'           => $guest->name,
            'phone_number'   => $guest->phone_number,
            'normalized_phone' => $guest->normalized_phone,
            'category'       => $guest->category,
            'greeting'       => $guest->greeting,
            'note'           => $guest->note,
            'guest_slug'     => $guest->guest_slug,
            'send_status'    => $guest->send_status->value,
            'rsvp_status'    => $guest->rsvp_status->value,
            'sent_count'     => $guest->sent_count,
            'first_opened_at'=> $guest->first_opened_at?->toISOString(),
            'last_opened_at' => $guest->last_opened_at?->toISOString(),
            'last_sent_at'   => $guest->last_sent_at?->toISOString(),
            'invitation'     => $guest->invitation ? [
                'id'    => $guest->invitation->id,
                'slug'  => $guest->invitation->slug,
                'title' => $guest->invitation->title,
            ] : null,
        ];
    }
}
