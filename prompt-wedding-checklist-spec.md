# Prompt — TheDay Wedding Checklist Feature Spec

You are implementing the **Wedding Checklist** feature for TheDay.

Context:
- TheDay is a premium, mobile-first digital wedding invitation SaaS for Indonesia.
- The wedding checklist is a helper feature inside the user dashboard.
- It helps couples track and manage their wedding preparation tasks.
- The current implementation already exists as a basic list view with filters and stats.
- This prompt defines improvements and implementation spec for the checklist feature.
- Users are mostly Indonesian couples aged 24-32, often accessing from mobile via browser.

---

## Current State (from screenshot)
The existing checklist page shows:
- Page title: "Checklist Pernikahan"
- Summary stats at top: Progress %, Selesai count, Perlu dikerjakan count, Diarsipkan count
- Filter bar: Semua status, Semua kategori, Semua prioritas, Urutan default
- Task list: each task has checkbox, title, optional description, category badge, priority badge
- Action icons per task: edit, delete
- Button top right: "+ Tambah Task"
- Categories visible: Administrasi, Venue, Vendor

This is a good foundation. The goal is to improve usability, especially on mobile.

---

## Core Product Decision
**Do not implement kanban drag-and-drop** for this feature.

Reasons:
- Checklist tasks only need two states: done or not done.
- Users want to scroll vertically and see all tasks at once.
- Kanban with multiple columns is very uncomfortable on mobile portrait view.
- The existing summary stats already cover progress visibility at a glance.
- Kanban adds UX friction without meaningful benefit for this use case.

The checklist must remain a **vertical list-based UI** with improvements.

---

## Improvements to Implement
Implement the following improvements over the current state:

### 1. Task Grouping by Category
- Group tasks under their category header.
- Show category name as a section header above its tasks.
- Each category group should be collapsible (expand/collapse).
- Default state: all groups expanded.
- Collapsed state: show group name + task count only.
- Categories should be ordered: Administrasi, Venue, Vendor, Undangan, Keuangan, Busana, Dekorasi, Dokumentasi, Lainnya.
- Custom categories added by user may appear after defaults.

### 2. Collapsible Category Groups
- Clicking category header toggles expand/collapse.
- Collapsed group shows a count badge, example: "Venue (3/5)".
- Persist collapse state per session, not per page refresh.
- Collapsing a group does not affect filtering.

### 3. Priority Visual Improvement
- High / Tinggi: red dot or strong red badge.
- Medium / Sedang: amber/orange dot or badge.
- Low / Rendah: gray dot or badge.
- Replace text-only badge with colored dot + label for better scan speed.

### 4. Task Completion Interaction
- Checkbox must be large enough for easy tap on mobile (min 44x44px touch target).
- Checking a task should visually dim/strikethrough the task row immediately.
- Transition should be smooth, not jarring.
- Unchecking should restore full opacity and remove strikethrough.
- Completed tasks should optionally move to bottom of their group or retain position.
- Add setting: "Pindahkan task selesai ke bawah" toggle.

### 5. Swipe Actions on Mobile
- Swipe left on a task row to reveal quick action buttons: Selesai, Edit, Hapus.
- Swipe right to mark as selesai directly.
- Swipe actions are optional on desktop.

### 6. Due Date / Milestone Support (optional enhancement)
- Each task may have an optional `due_date` field.
- If due_date is set and approaching or overdue, show visual indicator.
- Overdue: red date label.
- Approaching (within 7 days): amber date label.
- No due date: no indicator shown.
- Due dates are not required for tasks to function.

### 7. Bulk Actions
- Allow multi-select mode.
- In multi-select mode, show bulk action bar: Tandai Selesai, Arsipkan, Hapus.
- Activate multi-select by long press on mobile or checkbox click with modifier on desktop.
- Deactivate by tapping cancel or pressing escape.

### 8. Summary Stats
- Keep existing stats: Progress %, Selesai, Perlu dikerjakan, Diarsipkan.
- Update in real-time as tasks are checked or unchecked.
- Optionally add per-category progress mini bars inside each group header.

### 9. Filter Behavior Improvement
- Filter by status should update group headers to reflect filtered count.
- If a filter results in empty group, hide the group entirely.
- Show empty state message if no tasks match active filters.
- Filters should stack, example: category AND priority AND status at once.

### 10. Empty State
- When no tasks exist yet, show a friendly empty state with a CTA to add first task or load default template.
- Default template means preloaded default tasks for wedding preparation.

---

## Task Data Model
Each task should support these fields:

- `id` UUID primary key
- `invitation_id` UUID nullable (can be linked to specific invitation or be general)
- `user_id` UUID foreign key
- `title` string required
- `description` string nullable
- `category` string default: `Lainnya`
- `priority` enum: `tinggi`, `sedang`, `rendah` default: `sedang`
- `status` enum: `todo`, `done`, `archived` default: `todo`
- `due_date` date nullable
- `sort_order` unsigned integer
- `completed_at` timestamp nullable
- `created_at`
- `updated_at`

---

## Allowed Categories
Default allowed categories:
- Administrasi
- Venue
- Vendor
- Undangan
- Keuangan
- Busana
- Dekorasi
- Dokumentasi
- Lainnya

Allow user to input custom category string if none of defaults fit.
Store as string on the task record.

---

## Default Task Templates
If user has no tasks, offer to load default templates.
Suggested default tasks (minimum viable set):

Administrasi:
- Urus dokumen & buku nikah (Tinggi)
- Siapkan surat izin orang tua jika dibutuhkan (Sedang)
- Daftar & konfirmasi jadwal ke KUA (Tinggi)

Venue:
- Survei dan bandingkan venue resepsi (Tinggi)
- Booking venue resepsi & bayar DP (Tinggi)
- Konfirmasi ulang detail venue seperti layout, catering, parkir (Sedang)

Vendor:
- Survei dan pilih katering (Tinggi)
- Booking fotografer & videografer (Tinggi)
- Survei dan pilih WO atau MC (Sedang)
- Booking dekorasi (Sedang)

Undangan:
- Buat dan publish undangan digital di TheDay (Tinggi)
- Siapkan daftar tamu (Tinggi)
- Kirim undangan via WhatsApp (Tinggi)

Keuangan:
- Buat anggaran pernikahan (Tinggi)
- Lacak pengeluaran aktual vs anggaran (Sedang)

Busana:
- Pilih dan fitting busana pengantin (Tinggi)
- Konfirmasi busana pagar ayu dan pengawal (Sedang)

---

## Validation Rules

### Task Creation & Edit
- `title` required, min 3 chars, max 200 chars.
- `description` optional, max 1000 chars.
- `category` required, either from allowed list or custom string, max 50 chars.
- `priority` must be valid enum.
- `status` must be valid enum.
- `due_date` optional, must be valid date, cannot be in the past for new tasks only as warning (not hard error).
- `sort_order` managed server-side, not directly editable by user.

---

## Completion Status Logic

Progress % calculation:
- count of `status = done` / total non-archived tasks × 100
- round to nearest integer
- exclude archived tasks from both numerator and denominator

Selesai count:
- count where `status = done`

Perlu dikerjakan count:
- count where `status = todo`

Diarsipkan count:
- count where `status = archived`

Per-category progress:
- same logic but scoped per category

---

## Sort Order Rules
Default sort order:
- by `sort_order` ascending within each category group

User may reorder tasks within a group via drag-and-drop inside the group only.
Cross-group drag is not allowed because tasks are categorized.

When a new task is added, assign `sort_order = max(current group) + 1`.

---

## Archiving Rules
- Archived tasks are not shown in default view.
- Archived tasks are visible when filter "Diarsipkan" is active.
- Archiving does not delete the task.
- User may restore archived tasks.
- Bulk archive available via multi-select.

---

## Edge Cases

### 1. No tasks exist
- Show empty state with CTA to tambah task or load template.
- Do not show empty category groups.

### 2. All tasks completed
- Show celebration state at top: "Semua task selesai! 🎉"
- Progress shows 100%.

### 3. Category filter with no matching tasks
- Hide empty category groups.
- Show overall empty state if all groups are hidden.

### 4. Very long task title
- Wrap to max 2 lines in list view.
- Full title visible in edit modal.

### 5. Very long description
- Truncate in list view with expand inline or via edit modal.

### 6. Duplicate task title
- Allow, no uniqueness constraint on title.

### 7. Task completion timestamp
- Set `completed_at` when status changes to done.
- Clear `completed_at` if task is unchecked.

### 8. Sort order gap after deletion
- Normalize sort_order server-side if needed.
- Gaps are acceptable and should not break ordering.

### 9. Filter applied and task is checked
- Real-time update stats while filter active.
- Task may visually move or stay based on user preference setting.

### 10. Task with past due date
- Show overdue label in red.
- Do not block editing or completion.

### 11. User loads default template when tasks already exist
- Append default tasks, do not replace existing tasks.
- Avoid exact title duplicates optionally by checking before insert.

### 12. Custom category input
- Trim whitespace.
- Normalize to title case or preserve user input exactly.
- Do not allow empty custom category.

---

## UI Component Map
Suggested components:

- `ChecklistPage` — main page wrapper
- `ChecklistSummaryStats` — progress cards at top
- `ChecklistFilterBar` — filter dropdowns
- `ChecklistCategoryGroup` — collapsible group wrapper
- `ChecklistCategoryHeader` — group header with name, count, progress
- `ChecklistTaskItem` — single task row
- `ChecklistTaskCheckbox` — large tap-friendly checkbox
- `ChecklistTaskMeta` — category badge, priority badge, due date
- `ChecklistTaskActions` — edit, delete action icons
- `ChecklistSwipeRow` — swipe wrapper for mobile actions
- `ChecklistBulkActionBar` — multi-select action bar
- `ChecklistAddTaskModal` — form modal for add/edit task
- `ChecklistEmptyState` — empty state view
- `ChecklistDefaultTemplatePrompt` — prompt to load default tasks

---

## Responsive Rules

### Mobile
- Full-width list with generous row height
- Checkbox tap target minimum 44x44px
- Priority shown as colored dot, not full badge text
- Swipe actions available
- Category groups collapsible to manage scroll depth
- Add task button sticky at bottom or top right

### Desktop
- Wider layout with more room for description preview
- Hover state on rows
- Keyboard accessible: space to check, delete key to delete selected
- Multi-select via checkbox clicks
- Drag reorder within group via drag handle

---

## API Endpoints Needed
Implement these endpoints:

- `GET /dashboard/checklist` — list all tasks for user with grouping and stats
- `POST /dashboard/checklist` — create new task
- `PATCH /dashboard/checklist/{id}` — update task
- `PATCH /dashboard/checklist/{id}/status` — change status only (done/todo/archived)
- `DELETE /dashboard/checklist/{id}` — delete task
- `POST /dashboard/checklist/bulk` — bulk status update
- `POST /dashboard/checklist/load-template` — load default task templates
- `PATCH /dashboard/checklist/reorder` — update sort order within group

---

## Deliverables
Produce:

1. Laravel migration for `wedding_checklist_tasks` table
2. Laravel model with casts, relationships, and scopes
3. Controller with index, store, update, status patch, destroy, bulk, and template endpoints
4. Request validation classes
5. Vue component structure per component map
6. Responsive mobile-first Tailwind UI
7. Default task template seeder
8. Completion and stats calculation logic
9. Filter and sort logic
10. Edge case handling per spec

---

## Non-Goals
Do not implement:
- Kanban board
- Cross-category drag-and-drop
- Real-time collaborative checklist between multiple users
- Reminder notifications (may be future phase)
- Export to PDF (may be future phase)

---

## Acceptance Criteria
Implementation is successful when:
1. User can see all tasks grouped by category.
2. User can check/uncheck tasks with smooth interaction.
3. Progress stats update in real-time on check/uncheck.
4. Filters work correctly and stack.
5. User can add, edit, and delete tasks.
6. User can load default template when list is empty.
7. Mobile experience is comfortable with large tap targets.
8. Edge cases from this spec are handled gracefully.
9. Design is consistent with TheDay brand: warm, clean, premium.
