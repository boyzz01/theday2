# Mobile Bottom Navbar Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a 5-slot bottom tab bar for mobile dashboard navigation with a popover-based "More" menu for secondary items.

**Architecture:** Two new Vue components mounted in `DashboardLayout.vue`. Bottom bar uses Inertia `<Link>` for 4 primary tabs plus a button trigger for the More popover. Popover state is held in the layout (shared between the two components). Existing hamburger code is preserved but hidden via a `hidden` class.

**Tech Stack:** Vue 3 (Composition API, `<script setup>`), Inertia.js (`@inertiajs/vue3`), Tailwind CSS, Ziggy (for `route()` helper)

**Spec:** [`docs/superpowers/specs/2026-05-15-mobile-bottom-navbar-design.md`](../specs/2026-05-15-mobile-bottom-navbar-design.md)

---

## File Structure

| File | Action | Responsibility |
|---|---|---|
| `resources/js/Components/dashboard/MobileBottomNav.vue` | Create | Render fixed bottom bar with 5 tabs |
| `resources/js/Components/dashboard/MoreMenuPopover.vue` | Create | Render floating menu above More tab |
| `resources/js/Layouts/DashboardLayout.vue` | Modify | Mount components, hide hamburger, add pb-16 |

No backend, routes, or migrations change.

---

### Task 1: Create MobileBottomNav component

**Files:**
- Create: `resources/js/Components/dashboard/MobileBottomNav.vue`

- [ ] **Step 1: Create directory and component file with full template**

Write the file with this exact content:

```vue
<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const emit = defineEmits(['toggle-more']);

defineProps({
    moreOpen: {
        type: Boolean,
        default: false,
    },
});

const tabs = [
    {
        label: 'Home',
        routeName: 'dashboard',
        activePatterns: ['dashboard'],
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>`,
    },
    {
        label: 'Undangan',
        routeName: 'dashboard.invitations.index',
        activePatterns: ['dashboard.invitations.*'],
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>`,
    },
    {
        label: 'Budget',
        routeName: 'dashboard.budget-planner.index',
        activePatterns: ['dashboard.budget-planner.*'],
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>`,
    },
    {
        label: 'Planner',
        routeName: 'dashboard.checklist.index',
        activePatterns: ['dashboard.checklist.*'],
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>`,
    },
];

const isActive = (patterns) => {
    try {
        return patterns.some(p => route().current(p));
    } catch {
        return false;
    }
};

const morePatterns = [
    'dashboard.rsvp.*',
    'dashboard.guest-list.*',
    'dashboard.buku-tamu.*',
    'dashboard.templates',
    'dashboard.paket',
    'dashboard.transactions.*',
    'profile.*',
];

const isMoreActive = computed(() => {
    try {
        return morePatterns.some(p => route().current(p));
    } catch {
        return false;
    }
});
</script>

<template>
    <nav
        class="fixed bottom-0 inset-x-0 z-30 lg:hidden bg-white border-t border-stone-200 flex"
        style="padding-bottom: env(safe-area-inset-bottom)"
        role="navigation"
        aria-label="Mobile navigation"
    >
        <Link
            v-for="tab in tabs"
            :key="tab.routeName"
            :href="route(tab.routeName)"
            :aria-label="tab.label"
            :aria-current="isActive(tab.activePatterns) ? 'page' : undefined"
            class="flex-1 flex flex-col items-center justify-center py-2 min-h-[56px] text-[10px] font-medium transition-colors"
            :class="isActive(tab.activePatterns) ? 'text-[#73877C]' : 'text-stone-400'"
        >
            <svg class="w-6 h-6 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" v-html="tab.icon" />
            <span>{{ tab.label }}</span>
        </Link>

        <button
            type="button"
            :aria-label="'More menu'"
            :aria-expanded="moreOpen"
            :aria-current="isMoreActive ? 'page' : undefined"
            class="flex-1 flex flex-col items-center justify-center py-2 min-h-[56px] text-[10px] font-medium transition-colors cursor-pointer"
            :class="(moreOpen || isMoreActive) ? 'text-[#73877C]' : 'text-stone-400'"
            @click="emit('toggle-more')"
        >
            <svg class="w-6 h-6 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/>
            </svg>
            <span>More</span>
        </button>
    </nav>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/dashboard/MobileBottomNav.vue
git commit -m "feat(dashboard): add MobileBottomNav component"
```

---

### Task 2: Create MoreMenuPopover component

**Files:**
- Create: `resources/js/Components/dashboard/MoreMenuPopover.vue`

- [ ] **Step 1: Create component file with full template**

Write the file with this exact content:

```vue
<script setup>
import { computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const page = usePage();
const plan = computed(() => page.props.auth?.subscription);

const planBadge = computed(() => {
    const slug = plan.value?.plan_slug;
    if (slug === 'premium') return { text: 'Premium', class: 'bg-[#73877C] text-white' };
    return { text: 'Free', class: 'bg-stone-100 text-stone-600' };
});

const items = [
    { label: 'Tamu', routeName: 'dashboard.rsvp.index', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
    { label: 'Template', routeName: 'dashboard.templates', icon: 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z' },
    { label: 'Paket & Langganan', routeName: 'dashboard.paket', icon: 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z', badge: true },
    { label: 'Riwayat Pembayaran', routeName: 'dashboard.transactions.index', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' },
    { label: 'Pengaturan Akun', routeName: 'profile.edit', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' },
];

const handleEscape = (e) => {
    if (e.key === 'Escape' && props.open) emit('close');
};

onMounted(() => document.addEventListener('keydown', handleEscape));
onBeforeUnmount(() => document.removeEventListener('keydown', handleEscape));

watch(() => props.open, (val) => {
    if (val) document.body.style.overflow = 'hidden';
    else document.body.style.overflow = '';
});
</script>

<template>
    <!-- Backdrop -->
    <Transition name="fade">
        <div
            v-if="open"
            class="fixed inset-0 z-20 lg:hidden"
            @click="emit('close')"
        />
    </Transition>

    <!-- Popover -->
    <Transition name="popover">
        <div
            v-if="open"
            class="fixed bottom-[68px] right-2 z-40 w-60 bg-white rounded-xl shadow-xl border border-stone-100 overflow-hidden lg:hidden"
            role="menu"
            aria-label="More menu"
            style="padding-bottom: env(safe-area-inset-bottom)"
        >
            <Link
                v-for="item in items"
                :key="item.routeName"
                :href="route(item.routeName)"
                role="menuitem"
                class="flex items-center gap-3 px-4 py-3 text-sm text-stone-700 hover:bg-stone-50 transition-colors border-b border-stone-50 last:border-b-0"
                @click="emit('close')"
            >
                <svg class="w-5 h-5 text-stone-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                </svg>
                <span class="flex-1">{{ item.label }}</span>
                <span
                    v-if="item.badge"
                    class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                    :class="planBadge.class"
                >{{ planBadge.text }}</span>
                <svg class="w-4 h-4 text-stone-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </Link>

            <!-- Logout -->
            <Link
                href="/logout"
                method="post"
                as="button"
                role="menuitem"
                class="w-full flex items-center gap-3 px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition-colors border-t border-stone-100"
                @click="emit('close')"
            >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span class="flex-1 text-left">Logout</span>
            </Link>

            <!-- Tail arrow pointing to More tab -->
            <div class="absolute -bottom-1.5 right-6 w-3 h-3 bg-white border-r border-b border-stone-100 rotate-45" />
        </div>
    </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.popover-enter-active,
.popover-leave-active {
    transition: opacity 0.18s ease, transform 0.18s ease;
}
.popover-enter-from,
.popover-leave-to {
    opacity: 0;
    transform: translateY(8px);
}
</style>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/dashboard/MoreMenuPopover.vue
git commit -m "feat(dashboard): add MoreMenuPopover component"
```

---

### Task 3: Integrate into DashboardLayout

**Files:**
- Modify: `resources/js/Layouts/DashboardLayout.vue`

- [ ] **Step 1: Add imports and state**

In `<script setup>` section, after the existing `import` statements (around line 4), add:

```js
import MobileBottomNav from '@/Components/dashboard/MobileBottomNav.vue';
import MoreMenuPopover from '@/Components/dashboard/MoreMenuPopover.vue';
```

Then after `const sidebarOpen = ref(false);` (around line 11), add:

```js
const moreMenuOpen = ref(false);
```

- [ ] **Step 2: Hide the hamburger button**

Find this block (around lines 357–364):

```vue
                <!-- Mobile hamburger -->
                <button
                    class="lg:hidden p-2 -ml-1 rounded-lg text-stone-500 hover:bg-stone-100 transition-colors cursor-pointer"
                    @click="sidebarOpen = !sidebarOpen"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
```

Replace with (adding `hidden` to the class string — preserves code, just hides):

```vue
                <!-- Mobile hamburger (HIDDEN — replaced by MobileBottomNav, kept for rollback) -->
                <button
                    class="hidden lg:hidden p-2 -ml-1 rounded-lg text-stone-500 hover:bg-stone-100 transition-colors cursor-pointer"
                    @click="sidebarOpen = !sidebarOpen"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
```

- [ ] **Step 3: Add bottom padding to main content area on mobile**

Find the main content wrapper. After the header, locate the page content area. Find the closing `</header>` tag and the content area below it. Add `pb-16 lg:pb-0` class to the main content wrapper so the bottom bar doesn't cover content.

Locate this around line 352:

```vue
        <!-- ── Main content ─────────────────────────────────────── -->
        <div class="flex-1 flex flex-col min-w-0">
```

Replace with:

```vue
        <!-- ── Main content ─────────────────────────────────────── -->
        <div class="flex-1 flex flex-col min-w-0 pb-16 lg:pb-0">
```

- [ ] **Step 4: Mount MobileBottomNav and MoreMenuPopover at the end of the layout**

Find the closing `</div>` of the outermost wrapper (the `<div class="min-h-screen flex" ...>` element — at the very end of `<template>`, just before `</template>`).

Just before the outer closing `</div>`, add:

```vue
        <!-- Mobile bottom navigation -->
        <MobileBottomNav
            :more-open="moreMenuOpen"
            @toggle-more="moreMenuOpen = !moreMenuOpen"
        />
        <MoreMenuPopover
            :open="moreMenuOpen"
            @close="moreMenuOpen = false"
        />
```

- [ ] **Step 5: Commit**

```bash
git add resources/js/Layouts/DashboardLayout.vue
git commit -m "feat(dashboard): integrate MobileBottomNav and MoreMenuPopover into layout"
```

---

### Task 4: Browser verification

**Files:** None — manual testing.

- [ ] **Step 1: Start dev server**

```bash
npm run dev
```

In another terminal:
```bash
php artisan serve
```

- [ ] **Step 2: Open dashboard in mobile viewport**

Open browser to `http://theday2.test/dashboard` (or wherever dashboard is). Open DevTools (F12) → Toggle device toolbar (Ctrl+Shift+M) → select iPhone or set width to <1024px.

- [ ] **Step 3: Verify bottom bar visible**

- Bottom bar appears at viewport bottom
- 5 tabs visible: Home, Undangan, Budget, Planner, More
- Currently-active route's tab is colored `#73877C`, others gray

- [ ] **Step 4: Verify navigation**

Tap each tab in order. Each navigates to its route. After each navigation, the corresponding tab becomes the active (colored) one.

- [ ] **Step 5: Verify hamburger hidden**

In DevTools Elements panel, find the hamburger `<button>` in the header. It should exist in the DOM but have `hidden` class so `display: none` applies. Bar at the top should NOT show a hamburger icon — only the page title slot and avatar.

- [ ] **Step 6: Verify More popover**

- Tap "More" tab → popover slides up with arrow pointing to More tab
- More tab becomes colored when popover open
- Popover lists: Tamu, Template, Paket & Langganan (with plan badge), Riwayat Pembayaran, Pengaturan Akun, Logout
- Tap outside (backdrop) → popover closes
- Reopen → press Escape → popover closes
- Reopen → tap "Tamu" → navigates to `/dashboard/rsvp` and popover closes
- Reopen → tap "Paket & Langganan" → navigates correctly, badge shows correct plan

- [ ] **Step 7: Verify logout**

Tap "More" → tap "Logout". POST request fires to `/logout`. User redirected to home.

- [ ] **Step 8: Verify content not obscured**

Scroll to the bottom of any dashboard page (e.g., undangan list). The last item should be fully visible with space below it (because of `pb-16` on the main wrapper). Bottom bar does not cover content.

- [ ] **Step 9: Verify desktop unchanged**

Resize viewport to ≥1024px. Bottom bar disappears (`lg:hidden`). Sidebar appears as before. Hamburger button remains hidden (it's `hidden lg:hidden` so it's always hidden — that's fine because on desktop the sidebar handles nav).

- [ ] **Step 10: Verify no console errors**

DevTools Console should be clean — no warnings about missing routes, undefined props, or Vue compiler errors.

- [ ] **Step 11: Commit verification done (no code change, but note completion)**

If any issue found in Steps 3–10, fix and re-test. Once all pass, the feature is verified.
