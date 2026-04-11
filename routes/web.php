<?php

use App\Http\Controllers\Dashboard\BudgetPlanner\BudgetCategoryController;
use App\Http\Controllers\Dashboard\BudgetPlanner\BudgetItemController;
use App\Http\Controllers\Dashboard\BudgetPlanner\BudgetPlannerPageController;
use App\Http\Controllers\Dashboard\BudgetPlanner\InitializeBudgetPlannerController;
use App\Http\Controllers\Dashboard\BudgetPlanner\UpdateBudgetController;
use App\Http\Controllers\Dashboard\ChecklistController;
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

// ── Sitemap ──────────────────────────────────────────────────────────────────
Route::get('/sitemap.xml', function () {
    $pages = [
        ['url' => url('/'),          'priority' => '1.0', 'changefreq' => 'weekly'],
        ['url' => url('/templates'), 'priority' => '0.9', 'changefreq' => 'daily'],
        ['url' => url('/login'),     'priority' => '0.5', 'changefreq' => 'monthly'],
        ['url' => url('/register'),  'priority' => '0.6', 'changefreq' => 'monthly'],
    ];

    $xml  = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($pages as $page) {
        $xml .= '<url>';
        $xml .= '<loc>' . e($page['url']) . '</loc>';
        $xml .= '<lastmod>' . now()->toDateString() . '</lastmod>';
        $xml .= '<changefreq>' . $page['changefreq'] . '</changefreq>';
        $xml .= '<priority>' . $page['priority'] . '</priority>';
        $xml .= '</url>';
    }
    $xml .= '</urlset>';

    return response($xml, 200)->header('Content-Type', 'application/xml');
})->name('sitemap');

// ── Guest-accessible public routes (no auth required) ───────────────────────
Route::middleware('guest.session')->group(function () {
    Route::get('/templates',              [TemplateGalleryController::class, 'index'])->name('templates.gallery');
    Route::get('/templates/{template:slug}/demo', [TemplateGalleryController::class, 'demo'])->name('templates.demo');
    Route::get('/editor',                 [EditorController::class, 'create'])->name('editor.create');
});

Route::middleware(['auth', 'verified'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/templates', [TemplateController::class, 'index'])->name('templates');

    // Invitation list & wizard
    Route::get( '/invitations',                    [InvitationController::class, 'index'])->name('invitations.index');
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

    // ── Budget Planner ───────────────────────────────────────────────────
    Route::get( '/budget-planner',                    [BudgetPlannerPageController::class, 'index'])->name('budget-planner.index');
    Route::post('/budget-planner/initialize',         [InitializeBudgetPlannerController::class, 'store'])->name('budget-planner.initialize');
    Route::patch('/budget-planner/budget',            [UpdateBudgetController::class, 'update'])->name('budget-planner.budget.update');

    Route::get(   '/budget-planner/categories',         [BudgetCategoryController::class, 'index'])->name('budget-planner.categories.index');
    Route::post(  '/budget-planner/categories',         [BudgetCategoryController::class, 'store'])->name('budget-planner.categories.store');
    Route::patch( '/budget-planner/categories/{category}', [BudgetCategoryController::class, 'update'])->name('budget-planner.categories.update');
    Route::delete('/budget-planner/categories/{category}', [BudgetCategoryController::class, 'destroy'])->name('budget-planner.categories.destroy');

    Route::get(   '/budget-planner/items',         [BudgetItemController::class, 'index'])->name('budget-planner.items.index');
    Route::post(  '/budget-planner/items',         [BudgetItemController::class, 'store'])->name('budget-planner.items.store');
    Route::patch( '/budget-planner/items/{item}',  [BudgetItemController::class, 'update'])->name('budget-planner.items.update');
    Route::delete('/budget-planner/items/{item}',  [BudgetItemController::class, 'destroy'])->name('budget-planner.items.destroy');

    // ── Checklist ────────────────────────────────────────────────────────
    Route::get( '/checklist',                          [ChecklistController::class, 'index'])->name('checklist.index');
    Route::post('/checklist/initialize',               [ChecklistController::class, 'initialize'])->name('checklist.initialize');
    Route::get( '/checklist/tasks',                    [ChecklistController::class, 'tasks'])->name('checklist.tasks');
    Route::post('/checklist/tasks',                    [ChecklistController::class, 'store'])->name('checklist.tasks.store');
    Route::patch('/checklist/tasks/{id}',              [ChecklistController::class, 'update'])->name('checklist.tasks.update');
    Route::patch('/checklist/tasks/{id}/toggle',       [ChecklistController::class, 'toggle'])->name('checklist.tasks.toggle');
    Route::patch('/checklist/tasks/{id}/archive',      [ChecklistController::class, 'archive'])->name('checklist.tasks.archive');
    Route::patch('/checklist/tasks/{id}/restore',      [ChecklistController::class, 'restore'])->name('checklist.tasks.restore');
    Route::get(  '/checklist/summary',                 [ChecklistController::class, 'summary'])->name('checklist.summary');
    Route::patch('/checklist/event-date',              [ChecklistController::class, 'updateEventDate'])->name('checklist.event-date');
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
