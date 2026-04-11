<?php

// app/Services/ChecklistService.php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ChecklistTaskSource;
use App\Enums\ChecklistTaskStatus;
use App\Models\ChecklistTask;
use App\Models\ChecklistTemplate;
use App\Models\WeddingPlan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ChecklistService
{
    // ─── Initialization ───────────────────────────────────────────

    /**
     * Initialize default checklist from templates.
     * Idempotent: no-op if already initialized.
     */
    public function initialize(WeddingPlan $plan): void
    {
        if ($plan->isChecklistInitialized()) {
            return;
        }

        DB::transaction(function () use ($plan) {
            $templates = ChecklistTemplate::active()->ordered()->get();

            foreach ($templates as $i => $template) {
                $dueDate = $this->calculateDueDate($plan->event_date, $template->day_offset);

                $plan->checklistTasks()->create([
                    'source'      => ChecklistTaskSource::System,
                    'template_id' => $template->id,
                    'title'       => $template->title,
                    'description' => $template->description,
                    'category'    => $template->category,
                    'priority'    => $template->priority,
                    'status'      => ChecklistTaskStatus::Todo,
                    'due_date'    => $dueDate,
                    'sort_order'  => $i,
                ]);
            }

            $plan->update(['checklist_initialized_at' => now()]);
        });
    }

    // ─── Task CRUD ────────────────────────────────────────────────

    public function createTask(WeddingPlan $plan, array $data): ChecklistTask
    {
        $maxOrder = $plan->checklistTasks()->max('sort_order') ?? -1;

        return $plan->checklistTasks()->create([
            'source'      => ChecklistTaskSource::User,
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'category'    => $data['category'],
            'priority'    => $data['priority'] ?? 'medium',
            'status'      => ChecklistTaskStatus::Todo,
            'due_date'    => $data['due_date'] ?? null,
            'sort_order'  => $maxOrder + 1,
        ]);
    }

    public function updateTask(ChecklistTask $task, array $data): ChecklistTask
    {
        $wasModified = $task->is_user_modified;

        // Any edit by user sets is_user_modified=true on system tasks
        $isUserModified = $wasModified || $task->isSystemTask();

        $task->update([
            'title'            => $data['title']       ?? $task->title,
            'description'      => array_key_exists('description', $data) ? $data['description'] : $task->description,
            'category'         => $data['category']    ?? $task->category,
            'priority'         => $data['priority']    ?? $task->priority,
            'due_date'         => array_key_exists('due_date', $data) ? $data['due_date'] : $task->due_date,
            'is_user_modified' => $isUserModified,
        ]);

        return $task->refresh();
    }

    // ─── State Transitions ────────────────────────────────────────

    /**
     * Toggle todo <-> done.
     * Throws if task is archived.
     */
    public function toggle(ChecklistTask $task): ChecklistTask
    {
        if ($task->isArchived()) {
            abort(422, 'Archived task cannot be toggled. Restore it first.');
        }

        DB::transaction(function () use ($task) {
            if ($task->isDone()) {
                $task->update([
                    'status'       => ChecklistTaskStatus::Todo,
                    'completed_at' => null,
                ]);
            } else {
                $task->update([
                    'status'       => ChecklistTaskStatus::Done,
                    'completed_at' => now(),
                ]);
            }
        });

        return $task->refresh();
    }

    /**
     * Archive a task (todo or done → archived).
     */
    public function archive(ChecklistTask $task): ChecklistTask
    {
        if ($task->isArchived()) {
            abort(422, 'Task already archived.');
        }

        $task->update([
            'status'      => ChecklistTaskStatus::Archived,
            'archived_at' => now(),
        ]);

        return $task->refresh();
    }

    /**
     * Restore archived task → todo.
     */
    public function restore(ChecklistTask $task): ChecklistTask
    {
        if (! $task->isArchived()) {
            abort(422, 'Only archived tasks can be restored.');
        }

        $task->update([
            'status'      => ChecklistTaskStatus::Todo,
            'archived_at' => null,
            'completed_at' => null,
        ]);

        return $task->refresh();
    }

    // ─── Summary ─────────────────────────────────────────────────

    /**
     * Progress excludes archived tasks (per spec).
     */
    public function getSummary(WeddingPlan $plan): array
    {
        $counts = $plan->checklistTasks()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $todo     = (int) ($counts['todo']     ?? 0);
        $done     = (int) ($counts['done']     ?? 0);
        $archived = (int) ($counts['archived'] ?? 0);
        $total    = $todo + $done;

        return [
            'total'          => $total,
            'todo'           => $todo,
            'done'           => $done,
            'archived'       => $archived,
            'progress'       => $total > 0 ? round(($done / $total) * 100) : 0,
            'has_event_date' => $plan->hasEventDate(),
            'event_date'     => $plan->event_date?->format('Y-m-d'),
        ];
    }

    // ─── Recalculation ────────────────────────────────────────────

    /**
     * Recalculate due dates for system tasks not modified by user.
     * Called when event_date changes on wedding_plan.
     */
    public function recalculateDueDates(WeddingPlan $plan): void
    {
        $tasks = $plan->checklistTasks()
            ->systemUnmodified()
            ->whereNotNull('template_id')
            ->with('template')
            ->get();

        foreach ($tasks as $task) {
            $dueDate = $this->calculateDueDate($plan->event_date, $task->template?->day_offset);
            $task->update(['due_date' => $dueDate]);
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────

    private function calculateDueDate(?Carbon $eventDate, ?int $dayOffset): ?string
    {
        if ($eventDate === null || $dayOffset === null) {
            return null;
        }

        return $eventDate->copy()->addDays($dayOffset)->format('Y-m-d');
    }

    // ─── Query Helpers ────────────────────────────────────────────

    public function getTasks(WeddingPlan $plan, array $filters = []): Collection
    {
        $query = $plan->checklistTasks();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        return $query->orderBy('sort_order')
                     ->orderBy('due_date')
                     ->orderBy('created_at')
                     ->get();
    }
}
