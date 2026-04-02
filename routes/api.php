<?php

// routes/api.php

declare(strict_types=1);

use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\GuestDraftController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Invitation Editor
|--------------------------------------------------------------------------
| All routes use auth:sanctum which supports both session-cookie auth
| (Inertia SPA on same domain) and Bearer token auth.
|--------------------------------------------------------------------------
*/

// ── Public utility endpoints ──────────────────────────────────────────────
Route::post('/auth/check-email', function (Request $request) {
    $request->validate(['email' => ['required', 'email']]);
    return response()->json([
        'available' => ! User::where('email', $request->email)->exists(),
    ]);
});

// ── Guest Draft Routes ────────────────────────────────────────────────────
Route::middleware('guest.session')->group(function () {
    Route::post(  '/guest/drafts',         [GuestDraftController::class, 'upsert']);
    Route::get(   '/guest/drafts/current', [GuestDraftController::class, 'current']);
    Route::delete('/guest/drafts/current', [GuestDraftController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'guest.session'])->group(function () {
    Route::post('/guest/drafts/convert', [GuestDraftController::class, 'convert']);
});

// ── Authenticated Routes ───────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // ── Invitation ────────────────────────────────────────────────
    Route::post(   '/invitations',               [InvitationController::class, 'store']);
    Route::put(    '/invitations/{invitation}',   [InvitationController::class, 'update']);

    // ── Details ───────────────────────────────────────────────────
    Route::post('/invitations/{invitation}/details', [InvitationController::class, 'storeDetails']);

    // ── Events ────────────────────────────────────────────────────
    Route::post(  '/invitations/{invitation}/events',          [InvitationController::class, 'storeEvent']);
    Route::put(   '/invitations/{invitation}/events/{event}',  [InvitationController::class, 'updateEvent']);
    Route::delete('/invitations/{invitation}/events/{event}',  [InvitationController::class, 'deleteEvent']);

    // ── Gallery ───────────────────────────────────────────────────
    // NOTE: reorder must be declared before /{gallery} to avoid "reorder" being
    // captured as a gallery UUID.
    Route::put(   '/invitations/{invitation}/galleries/reorder',       [InvitationController::class, 'reorderGallery']);
    Route::post(  '/invitations/{invitation}/galleries',               [InvitationController::class, 'storeGallery']);
    Route::delete('/invitations/{invitation}/galleries/{gallery}',     [InvitationController::class, 'deleteGallery']);

    // ── Music ─────────────────────────────────────────────────────
    Route::post('/invitations/{invitation}/music', [InvitationController::class, 'storeMusic']);

    // ── Publish / Unpublish ───────────────────────────────────────
    Route::put('/invitations/{invitation}/publish',   [InvitationController::class, 'publish']);
    Route::put('/invitations/{invitation}/unpublish', [InvitationController::class, 'unpublish']);
});
