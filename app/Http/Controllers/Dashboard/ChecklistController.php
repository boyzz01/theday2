<?php

// app/Http/Controllers/Dashboard/ChecklistController.php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ChecklistTask;
use App\Models\WeddingPlan;
use App\Services\ChecklistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ChecklistController extends Controller
{
    public function __construct(private readonly ChecklistService $service) {}

    // ─── Page ─────────────────────────────────────────────────────

    public function index(): Response
    {
        $plan = $this->resolveOrCreatePlan();

        return Inertia::render('Dashboard/Checklist/Index', [
            'weddingPlan' => [
                'id'         => $plan->id,
                'event_date' => $plan->event_date?->format('Y-m-d'),
                'initialized' => $plan->isChecklistInitialized(),
            ],
        ]);
    }

    // ─── API: Initialize ──────────────────────────────────────────

    public function initialize(): JsonResponse
    {
        $plan = $this->resolveOrCreatePlan();

        if ($plan->isChecklistInitialized()) {
            return response()->json(['message' => 'Already initialized.'], 200);
        }

        $this->service->initialize($plan);

        return response()->json(['message' => 'Checklist initialized.'], 201);
    }

    // ─── API: Tasks list ──────────────────────────────────────────

    public function tasks(Request $request): JsonResponse
    {
        $plan = $this->resolveOrCreatePlan();

        $filters = $request->only(['status', 'category']);
        $tasks   = $this->service->getTasks($plan, $filters);

        return response()->json([
            'tasks' => $tasks->map(fn($t) => $this->taskResource($t))->values(),
        ]);
    }

    // ─── API: Create task ─────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $plan = $this->resolveOrCreatePlan();

        $data = $request->validate([
            'title'       => 'required|string|min:3|max:120',
            'category'    => 'required|string|in:administrasi,venue,vendor,busana,undangan,tamu,acara,dokumentasi,lainnya',
            'priority'    => 'sometimes|string|in:low,medium,high',
            'due_date'    => 'nullable|date|after_or_equal:today',
            'description' => 'nullable|string|max:500',
        ]);

        $task = $this->service->createTask($plan, $data);

        return response()->json($this->taskResource($task), 201);
    }

    // ─── API: Update task ─────────────────────────────────────────

    public function update(Request $request, string $id): JsonResponse
    {
        $task = $this->resolveTask($id);

        $data = $request->validate([
            'title'       => 'sometimes|string|min:3|max:120',
            'category'    => 'sometimes|string|in:administrasi,venue,vendor,busana,undangan,tamu,acara,dokumentasi,lainnya',
            'priority'    => 'sometimes|string|in:low,medium,high',
            'due_date'    => 'nullable|date',
            'description' => 'nullable|string|max:500',
        ]);

        $task = $this->service->updateTask($task, $data);

        return response()->json($this->taskResource($task));
    }

    // ─── API: Toggle ──────────────────────────────────────────────

    public function toggle(string $id): JsonResponse
    {
        $task = $this->resolveTask($id);
        $task = $this->service->toggle($task);

        return response()->json($this->taskResource($task));
    }

    // ─── API: Archive ─────────────────────────────────────────────

    public function archive(string $id): JsonResponse
    {
        $task = $this->resolveTask($id);
        $task = $this->service->archive($task);

        return response()->json($this->taskResource($task));
    }

    // ─── API: Restore ─────────────────────────────────────────────

    public function restore(string $id): JsonResponse
    {
        $task = $this->resolveTask($id);
        $task = $this->service->restore($task);

        return response()->json($this->taskResource($task));
    }

    // ─── API: Update event date ───────────────────────────────────

    public function updateEventDate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_date' => 'required|date|after_or_equal:today',
        ]);

        $plan = $this->resolveOrCreatePlan();
        $plan->update(['event_date' => $data['event_date']]);

        return response()->json(['event_date' => $plan->event_date->format('Y-m-d')]);
    }

    // ─── API: Summary ─────────────────────────────────────────────

    public function summary(): JsonResponse
    {
        $plan = $this->resolveOrCreatePlan();

        return response()->json($this->service->getSummary($plan));
    }

    // ─── Helpers ──────────────────────────────────────────────────

    /**
     * Get or create the wedding plan for the authenticated user.
     */
    private function resolveOrCreatePlan(): WeddingPlan
    {
        return WeddingPlan::firstOrCreate(
            ['user_id' => Auth::id()]
        );
    }

    /**
     * Resolve task and authorize ownership via wedding plan.
     */
    private function resolveTask(string $id): ChecklistTask
    {
        $task = ChecklistTask::findOrFail($id);

        if ($task->weddingPlan->user_id !== Auth::id()) {
            abort(403);
        }

        return $task;
    }

    /**
     * Consistent task shape for API responses.
     */
    private function taskResource(ChecklistTask $task): array
    {
        return [
            'id'               => $task->id,
            'source'           => $task->source->value,
            'title'            => $task->title,
            'description'      => $task->description,
            'category'         => $task->category->value,
            'priority'         => $task->priority->value,
            'status'           => $task->status->value,
            'due_date'         => $task->due_date?->format('Y-m-d'),
            'sort_order'       => $task->sort_order,
            'is_user_modified' => $task->is_user_modified,
            'completed_at'     => $task->completed_at?->toISOString(),
            'archived_at'      => $task->archived_at?->toISOString(),
            'created_at'       => $task->created_at->toISOString(),
        ];
    }
}
