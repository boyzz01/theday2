# Mobile Bottom Navbar for Dashboard — Design Spec

**Date:** 2026-05-15
**Branch:** `mobile-navigation`

## Context

Current dashboard mobile navigation uses a slide-in hamburger drawer triggered by a button in the header. UX goal: replace primary navigation on mobile with a bottom tab bar pattern (familiar from Instagram, Tokopedia, Gojek) for faster thumb access. Hamburger code is kept in place but hidden — safer rollback.

## Decisions Locked

| Decision | Choice |
|---|---|
| Pattern | Bottom Tab Bar |
| Breakpoint | Visible at `<lg` (<1024px); hidden at `lg+` |
| Items | 5 slots: Home, Undangan, Budget, Planner, More |
| "More" implementation | Popover floating above the More tab |
| Existing hamburger | Code retained, hidden via CSS (`hidden` class) |
| Desktop sidebar | No change |

## Bottom Bar Items

| Slot | Icon | Label | Route |
|---|---|---|---|
| 1 | home (heroicon) | Home | `dashboard` |
| 2 | mail (heroicon) | Undangan | `dashboard.invitations.index` (active pattern `dashboard.invitations.*`) |
| 3 | wallet (heroicon) | Budget | `dashboard.budget-planner.index` |
| 4 | clipboard-check (heroicon) | Planner | `dashboard.checklist.index` |
| 5 | dots-horizontal (heroicon) | More | popover trigger |

## More Popover Contents

Order matters (top → bottom):

1. **Tamu** — navigates to `dashboard.rsvp.index` (default sub-page, free for all plans)
2. **Template** — `dashboard.templates`
3. **Paket & Langganan** — `dashboard.paket` (with badge showing plan status)
4. **Riwayat Pembayaran** — `dashboard.transactions.index`
5. **Pengaturan Akun** — `profile.edit`
6. **Logout** — POST `/logout`

## Components

### `resources/js/Components/dashboard/MobileBottomNav.vue` (new)

**Responsibility:** Render the 5-slot bottom bar.

- Uses `<Link>` from `@inertiajs/vue3` for tabs 1–4
- Tab 5 ("More") is a `<button>` that toggles popover state
- Active state derived from `usePage().props.ziggy.location` or `route().current()`
- Styling: `fixed bottom-0 inset-x-0 z-30 lg:hidden`, white background, top border, `pb-[env(safe-area-inset-bottom)]` for iOS home indicator
- Active color: `#73877C` (project brand); inactive: `text-stone-400`

### `resources/js/Components/dashboard/MoreMenuPopover.vue` (new)

**Responsibility:** Floating menu list rendered above the More tab when open.

- Props: `open: Boolean`, emits `close`
- Position: `absolute bottom-16 right-2`, `w-56`, white card, shadow
- Tail arrow pointing down toward the More tab (CSS triangle)
- Tap outside → emits `close` (backdrop with `inset-0 z-20` capturing clicks)
- Items: icon + label + right chevron; Paket item shows plan badge ("Premium" or "Free")
- Logout uses `<Link method="post" href="/logout">`
- Animation: fade + slide-up (Vue `<Transition>`)

### `resources/js/Layouts/DashboardLayout.vue` (modified)

- Import and mount `<MobileBottomNav>` at the end of the main wrapper
- Local state: `const moreOpen = ref(false)` passed to `<MobileBottomNav @toggle-more="moreOpen = !moreOpen">` and `<MoreMenuPopover :open="moreOpen" @close="moreOpen = false">`
- Find existing hamburger button (around line 358–359 — `class="lg:hidden p-2 -ml-1 ..."`) and add `hidden` class so it never renders
- Main content wrapper: add `pb-16 lg:pb-0` so bottom bar doesn't cover content

## State Management

Local `ref` in `DashboardLayout.vue`. No store. Popover state lives in the layout because both `MobileBottomNav` (trigger) and `MoreMenuPopover` (rendered overlay) need to share it.

## Accessibility

- Each tab: `aria-label` matching label, `aria-current="page"` on active tab
- More popover: `role="menu"`, items `role="menuitem"`, focus trap when open, Escape key closes
- Min touch target: 44×44px per WCAG

## Testing Checklist

- [ ] Bottom bar visible at <1024px, hidden at ≥1024px
- [ ] Each tab navigates to correct route
- [ ] Active state highlights current tab (including nested routes like `dashboard.invitations.create`)
- [ ] Tap "More" opens popover with arrow pointing to More tab
- [ ] Tap outside popover closes it
- [ ] Tap a popover item navigates and closes popover
- [ ] Logout works from popover
- [ ] Hamburger button no longer rendered on mobile (verify with DevTools — element exists but `hidden`)
- [ ] Desktop sidebar unchanged
- [ ] Content area not obscured by bottom bar (pb-16 applied)
- [ ] iOS Safari: bottom bar respects safe area inset

## Out of Scope

- Push notifications badge on tabs
- Bottom bar hide-on-scroll behavior (always visible for v1)
- Customizable tab order
- Per-tab quick actions / long-press
