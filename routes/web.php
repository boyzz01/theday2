<?php

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\InvitationController;
use App\Http\Controllers\Dashboard\TemplateController;
use App\Http\Controllers\EditorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicInvitationController;
use App\Http\Controllers\TemplateGalleryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
})->name('home');

// ── Guest-accessible public routes (no auth required) ───────────────────────
Route::middleware('guest.session')->group(function () {
    Route::get('/templates',              [TemplateGalleryController::class, 'index'])->name('templates.gallery');
    Route::get('/templates/{template:slug}/demo', [TemplateGalleryController::class, 'demo'])->name('templates.demo');
    Route::get('/editor',                 [EditorController::class, 'create'])->name('editor.create');
});

Route::middleware(['auth', 'verified'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/templates', [TemplateController::class, 'index'])->name('templates');

    // Invitation wizard
    Route::get( '/invitations/create',             [InvitationController::class, 'create'])->name('invitations.create');
    Route::get( '/invitations/{invitation}/edit',  [InvitationController::class, 'edit'])->name('invitations.edit');
    Route::post('/invitations/from-template',      [InvitationController::class, 'createFromTemplate'])->name('invitations.from-template');

    // Invitation CRUD API
    Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
    Route::patch('/invitations/{invitation}', [InvitationController::class, 'update'])->name('invitations.update');

    // Sub-resource sync endpoints
    Route::patch('/invitations/{invitation}/details', [InvitationController::class, 'updateDetails'])->name('invitations.details');
    Route::put('/invitations/{invitation}/events', [InvitationController::class, 'syncEvents'])->name('invitations.events');
    Route::put('/invitations/{invitation}/gallery', [InvitationController::class, 'syncGallery'])->name('invitations.gallery');
    Route::put('/invitations/{invitation}/music', [InvitationController::class, 'syncMusic'])->name('invitations.music');
    Route::patch('/invitations/{invitation}/config', [InvitationController::class, 'updateConfig'])->name('invitations.config');
    Route::post('/invitations/{invitation}/publish', [InvitationController::class, 'publish'])->name('invitations.publish');

    // File uploads
    Route::post('/invitations/{invitation}/upload', [InvitationController::class, 'upload'])->name('invitations.upload');
    Route::post('/invitations/{invitation}/upload-audio', [InvitationController::class, 'uploadAudio'])->name('invitations.upload-audio');
});

// Keep legacy route alias so Breeze redirects still work
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin routes — protected by role:admin middleware
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Route::get('/', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
});

// ── Public invitation pages ─────────────────────────────────────────────
// IMPORTANT: keep this LAST so /{slug} doesn't swallow other routes.
// The where() constraint excludes known top-level paths.
$slugExclusion = '^(?!login|register|logout|dashboard|admin|templates|editor|profile|up|verify-email|confirm-password|forgot-password|reset-password|email).*';

Route::get( '/{slug}',          [PublicInvitationController::class, 'show'])->where('slug', $slugExclusion)->name('invitation.show');
Route::post('/{slug}/unlock',   [PublicInvitationController::class, 'unlock'])->where('slug', $slugExclusion)->name('invitation.unlock');
Route::post('/{slug}/rsvp',     [PublicInvitationController::class, 'rsvp'])->where('slug', $slugExclusion)->name('invitation.rsvp');
Route::post('/{slug}/messages', [PublicInvitationController::class, 'storeMessage'])->where('slug', $slugExclusion)->name('invitation.messages.store');
Route::get( '/{slug}/messages', [PublicInvitationController::class, 'messages'])->where('slug', $slugExclusion)->name('invitation.messages');

require __DIR__.'/auth.php';
