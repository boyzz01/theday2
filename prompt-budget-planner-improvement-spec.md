# Prompt — TheDay Budget Planner Improvement Spec

You are improving the **Budget Planner** feature for TheDay.

Context:
- TheDay is a premium, mobile-first digital wedding invitation SaaS for Indonesia.
- Budget Planner is a helper feature inside the user dashboard.
- It helps couples plan, track, and compare planned vs actual wedding spending.
- The current implementation already exists with basic category grouping and stats.
- This prompt defines improvements to make the feature significantly more useful and visual.
- Users are Indonesian couples aged 24-32, primarily accessing from mobile.
- Currency is always IDR (Rupiah). Format: "Rp 1.500.000" with dot separator.

---

## Current State (from screenshot)
The existing Budget Planner page shows:
- Page title: "Budget Planner"
- Subtitle: "Pantau rencana dan realisasi budget pernikahanmu."
- Top right buttons: "Kelola Kategori", "+ Tambah Item"
- Summary stats row: Total Budget (—), Total Planned (Rp 0), Terpakai (Rp 0), Sisa Budget (—)
- Banner: "Belum ada total budget yang diatur" with "Atur budget" CTA
- Two tabs: Kategori, Daftar Item
- Category list: Venue, Catering, Dekorasi, Busana — each with badge "Normal", Planned/Terpakai/Sisa row, and "Lihat (0)" button

---

## Core Improvement Goals
1. Add a donut chart visualization at the top to show budget composition and spending at a glance.
2. Replace meaningless "Normal" badge with meaningful status based on actual spending vs planned.
3. Add inline expand for category items instead of navigating away.
4. Improve onboarding for first-time users who haven't set total budget.
5. Improve mobile layout for category cards.
6. Add per-item DP and pelunasan tracking.

---

## Improvement 1: Donut Chart Summary

### What to build
Add a donut/ring chart in the summary section at the top of the page.

### Visual spec
- Ring chart with slices representing each category's planned or actual spending.
- Center of donut shows:
  - Primary label: "Terpakai" or "Total Planned" depending on whether spending has been recorded.
  - Amount: formatted IDR, example "Rp 12.500.000".
  - Secondary label: "dari Rp 25.000.000 budget" if total budget is set.
- Each slice is colored per category with consistent colors.
- Legend below or beside the chart shows category name + color dot + amount.

### Chart colors
Use TheDay brand palette:
- Venue: `#C8A26B` (primary gold)
- Catering: `#B5C4A8` (sage green)
- Dekorasi: `#D4A5A5` (dusty rose)
- Busana: `#A8B8C4` (slate blue)
- Dokumentasi: `#C4B8A8` (warm taupe)
- Undangan: `#B8C4A8` (light sage)
- Lainnya: `#D4C4A8` (warm cream)
- Custom categories: cycle through a warm neutral extended palette

### Layout
Desktop:
- Left: donut chart
- Right: summary stats cards (Total Budget, Total Planned, Terpakai, Sisa)

Mobile:
- Top: donut chart centered
- Below: summary stats in 2x2 grid

### Empty / unset state
If no items exist or total budget not set:
- Show donut as gray ring with center label: "Atur budget dulu"
- Show CTA button inside or below the ring: "Atur Total Budget"

### When total budget not set but items exist
- Show donut based on planned amounts.
- Center label: "Total Planned"
- Amount: sum of all planned items.
- No "sisa" calculation shown until total budget is set.

### When total budget is set
- Show donut based on terpakai (actual spending).
- Center label: "Terpakai"
- Secondary label: "dari Rp X total budget"
- If terpakai > total budget, center text turns red.

---

## Improvement 2: Meaningful Category Status Badge

### Replace "Normal" badge
Current badge "Normal" is meaningless. Replace with status computed from data.

### Status logic per category
Compare `terpakai` vs `planned` for each category:

- `Aman` (green): terpakai <= 80% of planned
- `Mendekati` (amber): terpakai > 80% and <= 100% of planned
- `Melebihi` (red): terpakai > planned
- `Belum ada data` (gray): no items or all items have zero terpakai

### Badge design
- Colored dot + label text
- Aman: green `#4CAF50`
- Mendekati: amber `#F59E0B`
- Melebihi: red `#EF4444`
- Belum ada data: gray `#9CA3AF`

### If no planned amount set for category
- Show gray badge "Belum diatur"

---

## Improvement 3: Inline Category Expand

### Replace "Lihat (0)" button
Instead of navigating to a separate page, clicking "Lihat" or the category header should expand inline to show items.

### Behavior
- Clicking category row or expand icon toggles item list inline.
- Default state: collapsed.
- Expanded state shows:
  - List of items under that category.
  - Each item shows: name, planned amount, terpakai amount, status.
  - "+ Tambah Item" button at bottom of expanded list.
- Animation: smooth slide down expand.

### Item row in expanded list
Each item shows:
- Item name
- Planned amount
- Terpakai amount
- Sisa (planned - terpakai)
- Status badge: Lunas, DP, Belum Bayar
- Edit and delete action icons

### Per-item payment tracking
Each budget item should support:
- `planned_amount`: total planned cost
- `dp_amount`: DP amount if applicable, nullable
- `dp_paid`: boolean, default false
- `final_amount`: final/pelunasan amount, nullable
- `final_paid`: boolean, default false
- `terpakai`: computed from dp_amount (if dp_paid) + final_amount (if final_paid)
- `notes`: string nullable

Payment status logic:
- `Lunas`: dp_paid = true AND final_paid = true, or no dp/final split and full amount paid manually
- `DP Terbayar`: dp_paid = true AND final_paid = false
- `Belum Bayar`: dp_paid = false AND final_paid = false
- `Lunas Sebagian`: custom partial payment recorded

---

## Improvement 4: Onboarding for First-Time Users

### Problem
Banner "Belum ada total budget yang diatur" is passive and easy to ignore.

### Solution
When user opens Budget Planner for the first time (no total budget set and no items):
- Show a friendly onboarding card at the top.
- Title: "Mulai rencanakan budget pernikahanmu"
- Description: "Set total budget dulu agar kamu bisa pantau pengeluaran dengan akurat."
- Two CTAs:
  - Primary: "Atur Total Budget"
  - Secondary: "Tambah Item Langsung"
- Once total budget is set, onboarding card dismisses permanently.

If total budget not set but items already exist:
- Show small inline notice: "Total budget belum diatur. Sisa budget tidak dapat dihitung."
- Link: "Atur sekarang"
- Do not block the rest of the UI.

---

## Improvement 5: Mobile Layout for Category Cards

### Current problem
Three columns Planned / Terpakai / Sisa are too cramped on mobile.

### Mobile layout
On mobile (max-width 768px):
- Replace 3-column row with vertical stacked layout inside each category card.
- Or show only the most important metric on the category header row:
  - Terpakai / Planned in a compact format: "Rp 12jt / Rp 20jt"
  - Status badge
  - Progress mini bar
- Full Planned/Terpakai/Sisa detail visible when expanded.

### Mini progress bar per category
Add a thin progress bar below category header showing:
- Fill: terpakai / planned ratio
- Color matches status badge color
- Width 100% of card, height 4px
- Label not needed, bar alone is enough at a glance

---

## Improvement 6: Tab "Daftar Item" Enhancement

### Current state
Tab Daftar Item purpose is unclear from current implementation.

### Improved Daftar Item tab
Show a flat list of all budget items across all categories with:
- Filter by category
- Filter by payment status: Semua, Lunas, DP Terbayar, Belum Bayar
- Sort by: Nama, Planned amount, Terpakai amount, Due date
- Each row shows: category chip, item name, planned, terpakai, status badge, edit/delete

---

## Data Model

### `wedding_budget_settings` table
- `id` UUID primary key
- `user_id` UUID foreign key
- `total_budget` bigint nullable (stored in IDR cents or full integer)
- `currency` string default `IDR`
- `notes` string nullable
- `created_at`
- `updated_at`

### `wedding_budget_categories` table
- `id` UUID primary key
- `user_id` UUID foreign key
- `name` string
- `color` string nullable (hex)
- `icon` string nullable
- `sort_order` unsigned integer
- `is_default` boolean default false
- `created_at`
- `updated_at`

### `wedding_budget_items` table
- `id` UUID primary key
- `user_id` UUID foreign key
- `category_id` UUID foreign key to `wedding_budget_categories.id`
- `name` string
- `planned_amount` bigint default 0
- `dp_amount` bigint nullable
- `dp_paid` boolean default false
- `dp_paid_at` timestamp nullable
- `final_amount` bigint nullable
- `final_paid` boolean default false
- `final_paid_at` timestamp nullable
- `terpakai_override` bigint nullable
- `due_date` date nullable
- `vendor_name` string nullable
- `notes` string nullable
- `sort_order` unsigned integer
- `created_at`
- `updated_at`

### Computed fields (not stored)
- `terpakai`: computed from dp + final paid amounts, or terpakai_override if set
- `sisa`: planned_amount - terpakai
- `payment_status`: computed from dp_paid and final_paid

---

## Validation Rules

### Budget settings
- `total_budget` optional, if set must be positive integer > 0
- `currency` read-only as IDR for now

### Category
- `name` required, max 50 chars
- `color` optional valid hex color
- `sort_order` managed server-side

### Budget item
- `name` required, max 200 chars
- `planned_amount` required, min 0
- `dp_amount` optional, if set must be <= planned_amount
- `final_amount` optional
- `dp_paid` boolean
- `final_paid` boolean
- `due_date` optional valid date
- `vendor_name` optional max 100 chars
- `notes` optional max 1000 chars

---

## Computed Summary Logic

### Total Planned
Sum of `planned_amount` across all non-deleted items.

### Total Terpakai
Sum of computed `terpakai` across all items.

### Sisa Budget
`total_budget - total_terpakai` if total_budget is set.
If total_budget not set, show "—".

### Per-category planned
Sum of planned_amount for items in that category.

### Per-category terpakai
Sum of terpakai for items in that category.

### Per-category sisa
Category planned - category terpakai.

### Per-category status
- Aman: terpakai <= 80% of planned
- Mendekati: terpakai > 80% and <= 100% of planned
- Melebihi: terpakai > planned
- Belum ada data: no items or all zero

---

## Default Categories
Seed these default categories with suggested colors:

- Venue — `#C8A26B`
- Catering — `#B5C4A8`
- Dekorasi — `#D4A5A5`
- Busana — `#A8B8C4`
- Dokumentasi — `#C4B8A8`
- Undangan — `#B8C4A8`
- Hiburan / Entertainment — `#D4B8A8`
- Transportasi — `#A8C4B8`
- Perhiasan — `#C8B8A8`
- Lainnya — `#D4C4A8`

---

## Edge Cases

### 1. No items and no total budget set
- Show onboarding card.
- Donut chart shows gray empty ring.

### 2. Items exist but total budget not set
- Show donut based on planned amounts.
- Sisa shown as "—".
- Show non-blocking notice to set total budget.

### 3. Terpakai exceeds total budget
- Sisa shown as negative with red color.
- Summary card "Sisa Budget" shows red negative amount.
- Donut chart shows overflow visual or full ring in red.

### 4. Terpakai exceeds category planned
- Category status badge: "Melebihi" (red).
- Item sisa shown as negative.

### 5. Category has no items
- Show "Belum ada item" inside expanded view.
- Show "+ Tambah Item" CTA.
- Planned, Terpakai, Sisa all show Rp 0.

### 6. All items paid (Lunas)
- Category badge: "Lunas" if all items final_paid.
- Or keep Aman badge if within budget.

### 7. Due date approaching
- If due_date within 7 days and not fully paid, show amber due date label.
- If due_date passed and not fully paid, show red overdue label.

### 8. dp_paid true but no dp_amount set
- Treat dp contribution as 0 for terpakai calculation.

### 9. Item planned_amount is 0
- Still show item in list.
- Status "Belum diatur" for that item.

### 10. Deleting a category
- If category has items, warn user.
- Option to reassign items to another category or delete all.
- Do not hard delete with items silently.

### 11. Very large amounts
- Format as "Rp 1.200.000.000" with proper dot separators.
- Do not abbreviate unless in chart legend (chart legend may show "Rp 1,2M").

### 12. Donut chart with many categories
- If more than 8 categories, group smallest ones into "Lainnya" slice.
- Tooltip on hover/tap shows exact amount.

### 13. Mobile chart interaction
- Tapping a donut slice highlights it and shows category name + amount in center.
- Tapping outside deselects.

### 14. User deletes all items
- Return to empty state view.
- Donut chart returns to gray empty ring.

---

## Chart Library Recommendation
Use one of:
- **Chart.js** with `vue-chartjs` wrapper — lightweight, good donut support
- **ApexCharts** with `vue3-apexcharts` — more interactive, supports tooltip and tap highlight
- Do not use D3 directly unless necessary — too heavy for this use case.

Preferred: ApexCharts for richer mobile interaction.

---

## UI Component Map

- `BudgetPlannerPage` — main page wrapper
- `BudgetDonutChart` — ring chart with center label
- `BudgetSummaryStats` — 4 cards: Total Budget, Planned, Terpakai, Sisa
- `BudgetOnboardingCard` — first-time user onboarding
- `BudgetCategoryGroup` — collapsible category row
- `BudgetCategoryHeader` — name, status badge, planned/terpakai compact, expand toggle
- `BudgetCategoryProgressBar` — thin 4px bar
- `BudgetItemRow` — item inside expanded category
- `BudgetItemPaymentBadge` — Lunas / DP / Belum Bayar
- `BudgetAddEditItemModal` — form modal for item add/edit
- `BudgetSetTotalModal` — modal to set total budget
- `BudgetDaftarItemTab` — flat item list with filter/sort
- `BudgetEmptyState` — empty state view

---

## API Endpoints Needed

- `GET /dashboard/budget` — get settings, all categories with items and computed totals
- `PATCH /dashboard/budget/settings` — set/update total budget
- `GET /dashboard/budget/categories` — list categories
- `POST /dashboard/budget/categories` — create category
- `PATCH /dashboard/budget/categories/{id}` — update category
- `DELETE /dashboard/budget/categories/{id}` — delete category
- `GET /dashboard/budget/items` — flat list of all items
- `POST /dashboard/budget/items` — create item
- `PATCH /dashboard/budget/items/{id}` — update item
- `PATCH /dashboard/budget/items/{id}/payment` — update dp_paid / final_paid
- `DELETE /dashboard/budget/items/{id}` — delete item

---

## Deliverables

1. Laravel migrations for `wedding_budget_settings`, `wedding_budget_categories`, `wedding_budget_items`
2. Laravel models with casts, relationships, computed attributes
3. Controller for all endpoints above
4. Request validation classes
5. Vue component structure per component map
6. Donut chart implementation using ApexCharts or Chart.js
7. Mobile-first Tailwind UI with responsive category cards
8. Default category seeder
9. Computed summary logic (planned, terpakai, sisa, per-category status)
10. Edge case handling per spec

---

## Non-Goals
Do not implement:
- Multi-currency support
- Budget export to PDF or Excel (future phase)
- Shared budget between two users (future phase)
- Bank account or payment gateway integration

---

## Acceptance Criteria
Implementation is successful when:
1. Donut chart renders correctly with category slices and center label.
2. Summary stats update in real-time when items are added or payment status changes.
3. Category status badge reflects actual spending vs planned.
4. Clicking a category row expands inline item list.
5. Each item supports DP and pelunasan tracking.
6. Onboarding card shows for first-time users without total budget.
7. Mobile layout is comfortable and not cramped.
8. All edge cases in this spec are handled gracefully.
9. Design is consistent with TheDay brand palette and premium feel.
