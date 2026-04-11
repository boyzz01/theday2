<?php

// app/Http/Controllers/Dashboard/DashboardController.php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Actions\BudgetPlanner\BuildBudgetSummaryAction;
use App\Actions\BudgetPlanner\InitializeWeddingBudgetAction;
use App\Enums\InvitationStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly InitializeWeddingBudgetAction $initBudget,
        private readonly BuildBudgetSummaryAction $buildSummary,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user()->load([
            'invitations' => fn ($q) => $q->with('template')->latest()->limit(3),
            'activeSubscription.plan',
        ]);

        $invitations = $user->invitations()->withCount(['rsvps', 'views'])->get();

        $stats = [
            'total_invitations' => $invitations->count(),
            'draft_count'       => $invitations->where('status', InvitationStatus::Draft)->count(),
            'published_count'   => $invitations->where('status', InvitationStatus::Published)->count(),
            'total_views'       => $invitations->sum('view_count'),
            'total_rsvps'       => $invitations->sum('rsvps_count'),
        ];

        $recentInvitations = $user->invitations()
            ->with('template')
            ->withCount('rsvps')
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn ($inv) => [
                'id'          => $inv->id,
                'title'       => $inv->title,
                'slug'        => $inv->slug,
                'event_type'  => $inv->event_type->value,
                'status'      => $inv->status->value,
                'view_count'  => $inv->view_count,
                'rsvps_count' => $inv->rsvps_count,
                'published_at' => $inv->published_at?->format('d M Y'),
                'expires_at'  => $inv->expires_at?->format('d M Y'),
                'template'    => $inv->template ? [
                    'name'          => $inv->template->name,
                    'thumbnail_url' => $inv->template->thumbnail_url,
                    'default_config' => $inv->template->default_config,
                ] : null,
            ]);

        $activePlan = $user->activeSubscription?->plan;

        // Budget widget
        $budget        = $this->initBudget->execute($request->user());
        $budgetSummary = $this->buildSummary->execute($budget);

        return Inertia::render('Dashboard/Index', [
            'stats'             => $stats,
            'recentInvitations' => $recentInvitations,
            'activePlan'        => $activePlan ? [
                'name'             => $activePlan->name,
                'max_invitations'  => $activePlan->max_invitations,
                'analytics_access' => $activePlan->analytics_access,
                'remove_watermark' => $activePlan->remove_watermark,
            ] : null,
            'budgetWidget' => [
                'total_budget'                => $budgetSummary['total_budget'],
                'total_actual'                => $budgetSummary['total_actual'],
                'usage_percentage'            => $budgetSummary['usage_percentage'],
                'overbudget_categories_count' => $budgetSummary['overbudget_categories_count'],
                'has_budget'                  => $budgetSummary['has_budget'],
                'is_total_overbudget'         => $budgetSummary['is_total_overbudget'],
                'formatted'                   => $budgetSummary['formatted'],
            ],
        ]);
    }
}
