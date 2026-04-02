<?php

// app/Http/Controllers/GuestDraftController.php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ConvertGuestDraftAction;
use App\Models\GuestDraft;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestDraftController extends Controller
{
    // POST /api/guest/drafts
    public function upsert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template_id' => ['nullable', 'uuid', 'exists:templates,id'],
            'data'        => ['nullable', 'array'],
            'step'        => ['nullable', 'integer', 'min:1'],
        ]);

        $sessionId = $request->cookie('guest_session_id')
            ?? $request->input('guest_session_id');

        if (empty($sessionId)) {
            return response()->json(['message' => 'Guest session tidak ditemukan.'], 422);
        }

        $draft = GuestDraft::bySession($sessionId)->notExpired()->first();

        if ($draft) {
            $draft->update($validated);
        } else {
            $draft = GuestDraft::create(array_merge(
                $validated,
                ['guest_session_id' => $sessionId]
            ));
        }

        return response()->json(['draft' => $draft]);
    }

    // GET /api/guest/drafts/current
    public function current(Request $request): JsonResponse
    {
        $sessionId = $request->cookie('guest_session_id')
            ?? $request->input('guest_session_id');

        if (empty($sessionId)) {
            return response()->json(['draft' => null]);
        }

        $draft = GuestDraft::bySession($sessionId)->notExpired()->first();

        return response()->json(['draft' => $draft]);
    }

    // DELETE /api/guest/drafts/current
    public function destroy(Request $request): JsonResponse
    {
        $sessionId = $request->cookie('guest_session_id')
            ?? $request->input('guest_session_id');

        if (! empty($sessionId)) {
            GuestDraft::bySession($sessionId)->delete();
        }

        return response()->json(['message' => 'Draft dihapus.']);
    }

    // POST /api/guest/drafts/convert  (auth required)
    public function convert(Request $request, ConvertGuestDraftAction $action): JsonResponse
    {
        $sessionId = $request->cookie('guest_session_id')
            ?? $request->input('guest_session_id');

        if (empty($sessionId)) {
            return response()->json(['invitation' => null]);
        }

        $invitation = $action->execute($request->user(), $sessionId);

        return response()->json(['invitation' => $invitation]);
    }
}
