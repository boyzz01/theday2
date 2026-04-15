# Prompt — TheDay Guest List Improvement Spec

You are improving the **Guest List** feature for TheDay.

Context:
- TheDay is a premium, mobile-first digital wedding invitation SaaS for Indonesia.
- Guest List is a core feature because invitations are shared via WhatsApp and RSVP is tracked in real time.
- The current implementation already has:
  - summary stat cards
  - search
  - filters for status, RSVP, category, sort
  - import
  - add guest
  - desktop table layout
  - mobile card layout
  - per-guest WhatsApp send button
- The current UI is already usable, but it needs improvement to become more efficient, clearer, and more premium.

Goal:
Improve Guest List UX and workflow on both desktop and mobile without changing the core architecture.

---

## Core Improvement Goals
1. Make the feature faster for real workflows: send, follow-up, filter, and manage guests.
2. Improve visual clarity between **send status** and **RSVP status**.
3. Add bulk actions for multi-guest workflows.
4. Simplify mobile layout so it feels lighter and less repetitive.
5. Keep WhatsApp sending as the primary action.
6. Make the UI feel calm, premium, and easy to scan.

---

## Existing UI State
Current desktop UI includes:
- stat cards: Total Tamu, Belum Kirim, Sudah Kirim, Sudah Dibuka, Hadir, Belum Respon
- search bar
- dropdown filters: status, RSVP, kategori, sorting
- buttons: Template WA, Import, Tambah Tamu
- guest table with columns: Tamu, Kategori, Status Kirim, RSVP, Terakhir Kirim, Aksi
- row actions: Kirim, Edit, Delete
- checkbox column for selection

Current mobile UI includes:
- stat cards in 2-column grid
- search + multiple dropdown filters
- Import + Tambah Tamu buttons
- guest cards showing name, number, category, send status, RSVP, and action buttons
- floating action button

---

## Improvement 1: Clarify Status Types
There are two different statuses in the feature and they must be visually distinct:
- `status_kirim` = invitation delivery/send state
- `status_rsvp` = guest response/attendance state

### Problem
Current UI makes these statuses feel too similar visually.
That reduces scan clarity.

### Required solution
Use different visual treatment:

#### Send status (`status_kirim`)
Allowed values:
- `belum_kirim`
- `sudah_kirim`
- `sudah_dibuka`

Visual style:
- more neutral / delivery-oriented
- recommended colors:
  - belum_kirim = gray
  - sudah_kirim = blue
  - sudah_dibuka = green

#### RSVP status (`status_rsvp`)
Allowed values:
- `belum_respon`
- `hadir`
- `tidak_hadir`
- `mungkin`

Visual style:
- response-oriented
- recommended colors:
  - belum_respon = amber
  - hadir = green
  - tidak_hadir = red
  - mungkin = purple or neutral violet

### Rule
Never style send status and RSVP status using the same badge treatment without distinction.
The user must be able to instantly differentiate:
- already sent vs not sent
- attendance response state

---

## Improvement 2: Clickable Stat Cards
Summary cards at the top should not be passive only.

### Required behavior
Each stat card acts as a quick filter:
- click `Belum Kirim` => filter guest list to status_kirim = belum_kirim
- click `Sudah Kirim` => filter to status_kirim = sudah_kirim
- click `Sudah Dibuka` => filter to status_kirim = sudah_dibuka
- click `Hadir` => filter to status_rsvp = hadir
- click `Belum Respon` => filter to status_rsvp = belum_respon
- click `Total Tamu` => reset to all guests

### UX behavior
- active selected stat card should show clear active state
- clicking active card again resets that quick filter
- stat card filters must sync with filter dropdown state

---

## Improvement 3: Bulk Actions
The desktop table already has checkboxes, so users expect bulk operations.

### Required bulk actions
When one or more guests are selected, show a bulk action bar.

Bulk actions:
- `Kirim WA`
- `Ubah kategori`
- `Export`
- `Hapus`

Optional later:
- `Kirim ulang`
- `Tandai sudah kirim`
- `Arsipkan`

### Desktop behavior
- bulk bar appears above the table when selection > 0
- include selected count, example: `3 tamu dipilih`

### Mobile behavior
- multi-select mode may be activated by long press on a guest card
- when active, show sticky bottom bulk action bar

### Safety
- delete bulk action must require confirmation
- WA send bulk action should open send flow or template selection before execution

---

## Improvement 4: Better Mobile Guest Card Hierarchy
Current mobile guest card is usable but still feels crowded and repetitive.

### Required layout hierarchy
For each guest card on mobile:

#### Row 1
- guest name (primary, strong emphasis)
- send status badge aligned right

#### Row 2
- phone number (secondary text)

#### Row 3
- small chips: category + RSVP status

#### Row 4
- main CTA button: `Kirim WA`

#### Row 5
- secondary actions in lightweight form:
  - `Edit`
  - `Hapus`
  - optional `Lainnya` menu

### Rule
Do not give equal visual weight to all actions.
`Kirim WA` must remain the only clearly dominant button.

---

## Improvement 5: Reduce Mobile Filter Clutter
Current mobile filter controls are useful but visually dense.

### Required solution
On mobile:
- keep search field always visible
- move advanced filters into one `Filter` trigger button or bottom sheet
- filter sheet includes:
  - status_kirim filter
  - RSVP filter
  - kategori filter
  - sorting

### Active filter display
After filters applied, show chips below search:
- `Status: Belum Kirim`
- `RSVP: Belum Respon`
- `Kategori: Keluarga`

Each chip should be removable individually.

### Desktop
Desktop can keep visible filter row.

---

## Improvement 6: Refine Desktop Table Workflow
Desktop table already works, but can be improved.

### Required improvements
- make row height comfortable but not too tall
- make guest name the strongest visual anchor
- keep phone number and invitation label secondary
- improve spacing between category, send status, RSVP, and action area
- keep WhatsApp button green and obvious
- edit and delete should remain lower-emphasis icons

### Last sent column
Current `Terakhir Kirim` should support these values:
- `Belum pernah`
- relative time, example `2 jam lalu`, `Kemarin`, `3 hari lalu`
- exact timestamp on tooltip or hover

Optional later:
- show send count, example `2x dikirim`

---

## Improvement 7: WhatsApp Workflow Enhancement
WhatsApp is the primary action and should feel first-class.

### Required per-guest actions
- `Kirim WA`
- if already sent, optionally label as `Kirim Ulang`
- `Copy link`
- `Preview pesan`

### Bulk send flow
When bulk sending:
- allow choosing a message template
- preview personalization variables before send
- show how many guests will be processed

### Message template context
Template WA should support variables such as:
- `{nama_tamu}`
- `{nama_mempelai}`
- `{link_undangan}`
- `{kategori_tamu}` optional

---

## Improvement 8: Better Guest Categories / Segmentation
Guest categories are already present and should become more useful.

### Default suggested categories
- Keluarga
- Teman
- Kantor
- Tetangga
- VIP
- Lainnya

### Category improvements
- category badge should remain subtle
- category should be filterable
- allow editing category in bulk
- allow custom categories

This helps send invitations in batches and analyze RSVP by segment.

---

## Improvement 9: Empty vs Filled State Consistency
Ensure the feature feels consistent between empty and filled states.

### Empty state should offer
- Tambah Tamu
- Import CSV
- Template WA
- Download template CSV optional

### Filled state should keep obvious CTA
Even when list is already populated:
- Tambah Tamu must remain visible
- Import remains secondary
- Template WA remains utility action

---

## Improvement 10: Floating Action Button Review
Current mobile UI shows a floating add button plus visible top actions.

### Rule
Avoid redundant CTA conflict.

Choose one of these approaches:
- keep top `Tambah Tamu` button and remove FAB
- or keep FAB but hide/simplify top add button on scroll/mobile

Recommended:
- if a clear `Tambah Tamu` button is already visible near the top, remove FAB
- keep FAB only when primary CTA may scroll away too far

FAB must not overlap important card actions or content.

---

## Suggested Data Model
Assume guest records already exist, but the feature should support these fields clearly:

- `id`
- `invitation_id`
- `name`
- `phone_number`
- `category`
- `status_kirim` enum: `belum_kirim`, `sudah_kirim`, `sudah_dibuka`
- `status_rsvp` enum: `belum_respon`, `hadir`, `tidak_hadir`, `mungkin`
- `last_sent_at` nullable timestamp
- `send_count` integer default 0
- `opened_at` nullable timestamp
- `responded_at` nullable timestamp
- `attendance_count` nullable integer
- `notes` nullable string
- `created_at`
- `updated_at`

---

## Required Filters
Implement or preserve these filters:
- search by guest name or phone number
- filter by send status
- filter by RSVP status
- filter by category
- sort by latest, oldest, name A-Z, name Z-A

### Filter logic
- all filters must stack
- quick stat-card filter must sync with dropdown filters
- clear all filters option must exist

---

## Edge Cases

### 1. Guest has no phone number
- disable `Kirim WA`
- show tooltip or helper message: `Nomor belum diisi`

### 2. Guest has invalid phone number
- show warning badge or validation state
- prevent sending until fixed

### 3. Guest already sent multiple times
- show latest send time in `Terakhir Kirim`
- optional send count indicator

### 4. Guest opened invitation but never responded
- send status = sudah_dibuka
- RSVP = belum_respon
- this combination must be supported clearly

### 5. Guest responded hadir with multiple attendance count
- show optional attendance count, example `Hadir (2 orang)`

### 6. Bulk send mixed guests including invalid numbers
- skip invalid ones with summary feedback
- do not fail entire batch silently

### 7. Guest without category
- show fallback category `Lainnya`

### 8. No results after filtering
- show empty filtered state with `Reset filter`

### 9. User selects guests then changes filter
- preserve selection only for still-visible records or clear safely with notice

### 10. Delete guest
- require confirmation on mobile and desktop

---

## UI Component Suggestions
- `GuestListPage`
- `GuestStatsCards`
- `GuestQuickFilterCard`
- `GuestSearchBar`
- `GuestFilterBar`
- `GuestFilterSheetMobile`
- `GuestActiveFilterChips`
- `GuestBulkActionBar`
- `GuestTableDesktop`
- `GuestCardMobile`
- `GuestSendStatusBadge`
- `GuestRsvpStatusBadge`
- `GuestRowActions`
- `GuestWhatsappSendFlow`
- `GuestImportModal`
- `GuestDeleteConfirmDialog`

---

## API / Backend Requirements
Support or improve these workflows:
- list guests with filters, sort, stats
- update guest category
- send individual WhatsApp invite
- bulk send WhatsApp invite
- bulk update category
- bulk export guests
- delete single and multiple guests

Suggested endpoints:
- `GET /dashboard/guests`
- `POST /dashboard/guests`
- `PATCH /dashboard/guests/{id}`
- `DELETE /dashboard/guests/{id}`
- `POST /dashboard/guests/bulk`
- `POST /dashboard/guests/send`
- `POST /dashboard/guests/send-bulk`
- `GET /dashboard/guests/export`

---

## Deliverables
Produce:
1. improved desktop guest table UI
2. improved mobile guest card UI
3. distinct send status vs RSVP badge system
4. clickable stat card quick filters
5. bulk action bar and multi-select workflow
6. mobile filter bottom sheet
7. refined WhatsApp send workflow
8. edge case handling from this spec
9. implementation consistent with TheDay premium mobile-first brand

---

## Non-Goals
Do not implement:
- full CRM complexity
- live chat with guests
- WhatsApp gateway internals beyond send flow hooks
- complex analytics dashboard in this phase

---

## Acceptance Criteria
Implementation is successful when:
1. send status and RSVP status are visually distinct and easy to scan
2. stat cards work as quick filters
3. bulk actions work for multi-guest management
4. mobile layout feels lighter and more focused
5. WhatsApp remains the primary action throughout the feature
6. filter and search workflows are efficient on both desktop and mobile
7. the overall UI feels cleaner, more premium, and more practical for real wedding invitation workflows
