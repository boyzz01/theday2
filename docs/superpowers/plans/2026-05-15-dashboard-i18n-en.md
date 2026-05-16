# Dashboard i18n (English) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add English language support to all 10 dashboard pages, with single source of truth (`lang/{en,id}.json`) shared between Laravel backend and Vue frontend via Inertia.

**Architecture:** vue-i18n v9 (composition API) on the frontend, hydrated from Inertia shared props. Backend reads same JSON file via Laravel's native `lang_path()` JSON support, exposing translations through `HandleInertiaRequests::share()`. Locale persists in localStorage; toggle button on top of dashboard sidebar swaps locale and triggers Inertia partial reload of `translations` prop.

**Tech Stack:** Laravel 13.9, PHP 8.4, Vue 3, Inertia 2, vue-i18n 9, Vite 7.

**Spec reference:** `docs/superpowers/specs/2026-05-15-dashboard-i18n-en-design.md`.

**Pre-existing state (verified):**
- `HandleInertiaRequests::share()` line 74-77 already shares a `translations` key but only contains `auth.php`. Will be replaced with full JSON contents.
- `config/app.php` line 81 sets `'locale' => env('APP_LOCALE', 'en')`. Default fallback is `en` not `id`. Plan keeps Laravel default at `en` (server-side) but frontend default stays `id` per spec — frontend localStorage initializer overrides.
- `AppNavbar.vue` already has a language toggle button (line 39-47) using `tLegacy`-equivalent inline pattern. Will refactor in Task 8.
- `DashboardLayout.vue` does NOT use `AppNavbar` and has no language toggle. Will mount toggle in Task 8.
- `vue-i18n` is NOT installed.
- `Dashboard/GuestList/Index.vue` was referenced in spec but not found earlier — verify and skip if absent.

**Testing pragmatism:** Pure UI string translation does not benefit from per-string TDD. Tests target the **infrastructure** (composable, middleware, lang-check script). Per-page migration relies on **manual QA + lang-check script** for verification. This is consistent with the spec's testing strategy.

---

## File Structure

**Create:**
- `lang/en.json` — full EN translations (root namespace)
- `lang/id.json` — full ID translations (root namespace)
- `resources/js/Composables/i18n.js` — vue-i18n instance factory
- `resources/js/Components/LanguageSwitcher.vue` — toggle button component (extracted, reusable)
- `scripts/lang-check.js` — Node script validating key parity between EN/ID
- `tests/Feature/InertiaTranslationsShareTest.php` — middleware integration test

**Modify:**
- `package.json` — add `vue-i18n` dependency
- `resources/js/app.js` — register vue-i18n plugin, hydrate messages, wire Inertia router success hook
- `resources/js/Composables/useLocale.js` — refactor to wrap vue-i18n, expose `t` (key-based) + `tLegacy(id, en)` for backward compat
- `app/Http/Middleware/HandleInertiaRequests.php` — replace `translations` array with JSON file content keyed by current locale
- `resources/js/Components/AppNavbar.vue` — replace inline toggle with `<LanguageSwitcher />`, keep `tLegacy` calls for `t('Harga','Pricing')` etc
- `resources/js/Layouts/DashboardLayout.vue` — mount `<LanguageSwitcher />` in topbar; migrate `navItems[].label` to `t('nav.*')`
- `resources/js/Pages/Dashboard/Checklist/Index.vue` — migrate strings
- `resources/js/Pages/Dashboard/BudgetPlanner/Index.vue` — migrate strings
- `resources/js/Pages/Dashboard/BukuTamu/Index.vue` — migrate strings
- `resources/js/Pages/Dashboard/GuestList/Index.vue` — migrate strings (if file exists)
- `resources/js/Pages/Dashboard/Rsvp/Index.vue` + `Show.vue` — migrate strings
- `resources/js/Pages/Dashboard/Transactions/Index.vue` — migrate strings
- `resources/js/Pages/Dashboard/Invitations/Index.vue` + `Create.vue` + `Customize.vue` — migrate strings
- `resources/js/Pages/Dashboard/Paket.vue` — migrate strings
- `resources/js/Pages/Dashboard/Templates.vue` — migrate strings
- `resources/js/Pages/Dashboard/Index.vue` — migrate strings (largest, last)
- `composer.json` (optional) — add `lang-check` script alias

---

## Task 1: Install vue-i18n

**Files:**
- Modify: `package.json`

- [ ] **Step 1: Install dependency**

```bash
npm install vue-i18n@^9
```

Expected: `package.json` `dependencies` (or `devDependencies`) gains `"vue-i18n": "^9.x.x"`. `package-lock.json` updated.

- [ ] **Step 2: Verify install**

```bash
npm ls vue-i18n
```

Expected output contains `vue-i18n@9.x.x`.

- [ ] **Step 3: Commit**

```bash
rtk git add package.json package-lock.json
rtk git commit -m "chore: add vue-i18n dependency"
```

---

## Task 2: Create initial lang JSON files

Create `lang/en.json` and `lang/id.json` with the foundational namespaces (`common`, `nav`, `validation`). Page-specific namespaces will be filled in their migration tasks.

**Files:**
- Create: `lang/en.json`
- Create: `lang/id.json`

- [ ] **Step 1: Create `lang/id.json`**

```json
{
  "common": {
    "save": "Simpan",
    "cancel": "Batal",
    "delete": "Hapus",
    "edit": "Edit",
    "loading": "Memuat...",
    "search": "Cari",
    "back": "Kembali",
    "next": "Lanjut",
    "yes": "Ya",
    "no": "Tidak",
    "confirm": "Konfirmasi",
    "close": "Tutup",
    "submit": "Kirim",
    "update": "Perbarui",
    "create": "Buat",
    "view": "Lihat",
    "more": "Lainnya",
    "actions": "Aksi"
  },
  "nav": {
    "dashboard": "Dashboard",
    "myInvitations": "Undangan Saya",
    "guests": "Tamu",
    "guestList": "Guest List Manager",
    "rsvp": "RSVP",
    "ucapan": "Ucapan",
    "weddingPlanner": "Wedding Planner",
    "budgetPlanner": "Budget Planner",
    "templates": "Template",
    "paket": "Paket & Langganan",
    "transactions": "Riwayat Pembayaran",
    "settings": "Pengaturan Akun",
    "logout": "Keluar"
  },
  "validation": {
    "required": ":field wajib diisi",
    "email": "Format email tidak valid",
    "min": ":field minimal :min karakter",
    "max": ":field maksimal :max karakter"
  },
  "dashboard": {
    "index": {},
    "paket": {},
    "templates": {},
    "checklist": {},
    "budget": {},
    "guests": {},
    "rsvp": {},
    "transactions": {},
    "bukuTamu": {},
    "invitations": {}
  }
}
```

- [ ] **Step 2: Create `lang/en.json`**

```json
{
  "common": {
    "save": "Save",
    "cancel": "Cancel",
    "delete": "Delete",
    "edit": "Edit",
    "loading": "Loading...",
    "search": "Search",
    "back": "Back",
    "next": "Next",
    "yes": "Yes",
    "no": "No",
    "confirm": "Confirm",
    "close": "Close",
    "submit": "Submit",
    "update": "Update",
    "create": "Create",
    "view": "View",
    "more": "More",
    "actions": "Actions"
  },
  "nav": {
    "dashboard": "Dashboard",
    "myInvitations": "My Invitations",
    "guests": "Guests",
    "guestList": "Guest List Manager",
    "rsvp": "RSVP",
    "ucapan": "Greetings",
    "weddingPlanner": "Wedding Planner",
    "budgetPlanner": "Budget Planner",
    "templates": "Templates",
    "paket": "Plans & Subscription",
    "transactions": "Payment History",
    "settings": "Account Settings",
    "logout": "Logout"
  },
  "validation": {
    "required": ":field is required",
    "email": "Invalid email format",
    "min": ":field minimum :min characters",
    "max": ":field maximum :max characters"
  },
  "dashboard": {
    "index": {},
    "paket": {},
    "templates": {},
    "checklist": {},
    "budget": {},
    "guests": {},
    "rsvp": {},
    "transactions": {},
    "bukuTamu": {},
    "invitations": {}
  }
}
```

- [ ] **Step 3: Validate JSON**

```bash
node -e "JSON.parse(require('fs').readFileSync('lang/id.json'))"
node -e "JSON.parse(require('fs').readFileSync('lang/en.json'))"
```

Expected: no output (success). Any error = malformed JSON, fix before commit.

- [ ] **Step 4: Commit**

```bash
rtk git add lang/en.json lang/id.json
rtk git commit -m "feat(i18n): add foundational en.json and id.json translation files"
```

---

## Task 3: Create lang-check.js script

Validates key parity between `lang/en.json` and `lang/id.json`. Exits 1 if any key exists in one file but not the other.

**Files:**
- Create: `scripts/lang-check.js`

- [ ] **Step 1: Write the script**

```js
// scripts/lang-check.js
const fs = require('fs');
const path = require('path');

const en = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'lang', 'en.json'), 'utf8'));
const id = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'lang', 'id.json'), 'utf8'));

function flatten(obj, prefix = '') {
  const out = [];
  for (const [k, v] of Object.entries(obj)) {
    const key = prefix ? `${prefix}.${k}` : k;
    if (v && typeof v === 'object' && !Array.isArray(v)) {
      out.push(...flatten(v, key));
    } else {
      out.push(key);
    }
  }
  return out;
}

const enKeys = new Set(flatten(en));
const idKeys = new Set(flatten(id));

const missingInEn = [...idKeys].filter(k => !enKeys.has(k));
const missingInId = [...enKeys].filter(k => !idKeys.has(k));

if (missingInEn.length === 0 && missingInId.length === 0) {
  console.log(`OK — ${enKeys.size} keys consistent between en.json and id.json`);
  process.exit(0);
}

if (missingInEn.length) {
  console.error(`Missing in en.json (${missingInEn.length}):`);
  missingInEn.forEach(k => console.error(`  - ${k}`));
}
if (missingInId.length) {
  console.error(`Missing in id.json (${missingInId.length}):`);
  missingInId.forEach(k => console.error(`  - ${k}`));
}
process.exit(1);
```

- [ ] **Step 2: Run script — expect pass**

```bash
node scripts/lang-check.js
```

Expected: `OK — N keys consistent between en.json and id.json`. Exit 0.

- [ ] **Step 3: Negative-path verification**

Temporarily add a key only to `lang/en.json` (e.g. add `"common.testkey": "test"`), run script, expect exit 1 with "Missing in id.json: common.testkey". Then revert the change.

```bash
# After verifying failure mode, revert
rtk git checkout lang/en.json
node scripts/lang-check.js
```

Expected after revert: exit 0 again.

- [ ] **Step 4: Commit**

```bash
rtk git add scripts/lang-check.js
rtk git commit -m "feat(i18n): add lang-check script for en/id key parity"
```

---

## Task 4: Update HandleInertiaRequests middleware

Replace existing `translations` (auth.php only) with full JSON file content for the active locale. Resolve locale from header → cookie → `config('app.locale')`.

**Files:**
- Modify: `app/Http/Middleware/HandleInertiaRequests.php` (lines 74-77 + add locale resolution)
- Test: `tests/Feature/InertiaTranslationsShareTest.php`

- [ ] **Step 1: Write failing test**

Create `tests/Feature/InertiaTranslationsShareTest.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Define a dummy Inertia route for testing share()
    Route::get('/__test_inertia_share', function () {
        return inertia('Dashboard/Index'); // any existing component
    });
});

it('shares translations from lang/id.json when locale is id', function () {
    $response = $this->withHeader('X-Locale', 'id')->get('/__test_inertia_share');
    $response->assertOk();
    $page = $response->viewData('page');
    expect($page['props']['locale'])->toBe('id');
    expect($page['props']['translations'])->toBeArray();
    expect($page['props']['translations']['common']['save'] ?? null)->toBe('Simpan');
});

it('shares translations from lang/en.json when locale is en', function () {
    $response = $this->withHeader('X-Locale', 'en')->get('/__test_inertia_share');
    $response->assertOk();
    $page = $response->viewData('page');
    expect($page['props']['locale'])->toBe('en');
    expect($page['props']['translations']['common']['save'] ?? null)->toBe('Save');
});

it('falls back to config app.locale when no header or cookie', function () {
    config(['app.locale' => 'id']);
    $response = $this->get('/__test_inertia_share');
    $page = $response->viewData('page');
    expect($page['props']['locale'])->toBe('id');
});
```

- [ ] **Step 2: Run test — expect fail**

```bash
php artisan test --filter=InertiaTranslationsShareTest
```

Expected: FAIL — translations key returns auth.php content, not JSON; `locale` prop missing.

- [ ] **Step 3: Modify `HandleInertiaRequests::share()`**

Replace lines 74-77 (the existing `'translations' => [...]` block) with the new locale-aware sharing. The full updated `share()` method:

```php
public function share(Request $request): array
{
    $user = $request->user();

    $locale = $request->header('X-Locale')
        ?? $request->cookie('locale')
        ?? config('app.locale');

    if (! in_array($locale, ['id', 'en'], true)) {
        $locale = 'id';
    }
    app()->setLocale($locale);

    $translationsPath = lang_path("{$locale}.json");
    $translations = file_exists($translationsPath)
        ? json_decode(file_get_contents($translationsPath), true) ?? []
        : [];

    return [
        ...parent::share($request),
        'auth' => [
            'user' => $user ? [
                'id'                      => $user->id,
                'name'                    => $user->name,
                'email'                   => $user->email,
                'avatar_url'              => $user->avatar_url,
                'onboarding_completed'    => $user->hasCompletedOnboarding(),
            ] : null,
            'subscription' => $user ? (function () use ($user) {
                $sub = $user->activeSubscription;
                if (! $sub) return null;
                return [
                    'plan_name'           => $sub->plan->name,
                    'plan_slug'           => $sub->plan->slug,
                    'max_invitations'     => $sub->plan->max_invitations,
                    'status'              => $sub->status,
                    'remove_watermark'    => $sub->plan->remove_watermark,
                    'analytics_access'    => $sub->plan->analytics_access,
                    'custom_music'        => $sub->plan->custom_music,
                    'expires_at'          => $sub->expires_at?->format('d M Y'),
                    'days_remaining'      => $sub->daysRemaining(),
                    'in_grace_period'     => $sub->isInGracePeriod(),
                    'grace_days_remaining' => $sub->graceDaysRemaining(),
                ];
            })() : null,
            'isGuest' => ! $user,
        ],
        'can_create_invitation' => fn () => $user ? (function () use ($user) {
            $base   = $user->currentPlan()?->max_invitations
                ?? \App\Models\Plan::where('slug', 'free')->value('max_invitations')
                ?? 1;
            $addons = $user->invitationAddons()
                ->where('expires_at', '>', now())
                ->sum('quantity');
            return $user->invitations()->count() < ($base + $addons);
        })() : true,
        'checklist_todo' => fn () => $user
            ? ChecklistTask::whereHas('weddingPlan', fn ($q) => $q->where('user_id', $user->id))
                ->todo()
                ->count()
            : 0,
        'flash' => [
            'success' => fn () => $request->session()->get('success'),
            'error'   => fn () => $request->session()->get('error'),
        ],
        'locale' => $locale,
        'translations' => $translations,
    ];
}
```

- [ ] **Step 4: Run test — expect pass**

```bash
php artisan test --filter=InertiaTranslationsShareTest
```

Expected: 3 tests pass.

- [ ] **Step 5: Commit**

```bash
rtk git add app/Http/Middleware/HandleInertiaRequests.php tests/Feature/InertiaTranslationsShareTest.php
rtk git commit -m "feat(i18n): share locale-aware JSON translations via Inertia middleware"
```

---

## Task 5: Setup vue-i18n in app.js

Register vue-i18n plugin, hydrate messages from Inertia `translations` shared prop, wire Inertia router `success` event to refresh messages on partial reload.

**Files:**
- Create: `resources/js/Composables/i18n.js`
- Modify: `resources/js/app.js`

- [ ] **Step 1: Create i18n factory**

`resources/js/Composables/i18n.js`:

```js
import { createI18n } from 'vue-i18n';

const STORED = (typeof localStorage !== 'undefined' && localStorage.getItem('theday_lang')) || null;
const initialLocale = STORED && ['id', 'en'].includes(STORED) ? STORED : 'id';

export const i18n = createI18n({
    legacy: false,
    locale: initialLocale,
    fallbackLocale: 'id',
    messages: { id: {}, en: {} },
    missingWarn: false,
    fallbackWarn: false,
});

export function setI18nMessages(locale, messages) {
    if (!messages || typeof messages !== 'object') return;
    i18n.global.setLocaleMessage(locale, messages);
}
```

- [ ] **Step 2: Update `resources/js/app.js`**

Replace entire file:

```js
import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import axios from 'axios';
import { i18n, setI18nMessages } from './Composables/i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

axios.defaults.headers.common['X-Locale'] = i18n.global.locale.value;

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const initial = props.initialPage?.props ?? {};
        if (initial.translations && initial.locale) {
            setI18nMessages(initial.locale, initial.translations);
        }

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18n)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// Refresh messages whenever Inertia navigates / partial-reloads
router.on('success', (event) => {
    const props = event.detail?.page?.props ?? {};
    if (props.translations && props.locale) {
        setI18nMessages(props.locale, props.translations);
    }
});
```

- [ ] **Step 3: Build to verify no syntax errors**

```bash
rtk npm run build
```

Expected: build succeeds. Look for `vue-i18n` chunk in output. No errors.

- [ ] **Step 4: Commit**

```bash
rtk git add resources/js/Composables/i18n.js resources/js/app.js
rtk git commit -m "feat(i18n): register vue-i18n plugin and hydrate from Inertia props"
```

---

## Task 6: Refactor useLocale composable

Wrap vue-i18n. Keep the existing API surface (`locale`, `setLocale`, `toggleLocale`) for callers, expose new `t(key)` from vue-i18n, and add `tLegacy(id, en)` for not-yet-migrated callers.

**Files:**
- Modify: `resources/js/Composables/useLocale.js`

- [ ] **Step 1: Replace file content**

```js
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { i18n } from './i18n';

const LANG_KEY = 'theday_lang';

export function useLocale() {
    const { t } = useI18n();
    const locale = i18n.global.locale;

    function setLocale(lang) {
        if (!['id', 'en'].includes(lang)) return;
        locale.value = lang;
        try { localStorage.setItem(LANG_KEY, lang); } catch (_) {}
        axios.defaults.headers.common['X-Locale'] = lang;
        // Refresh translations prop from server for the new locale
        router.reload({ only: ['translations', 'locale'] });
    }

    function toggleLocale() {
        setLocale(locale.value === 'id' ? 'en' : 'id');
    }

    // Deprecated — for callers not yet migrated to t('key')
    function tLegacy(id, en) {
        return locale.value === 'en' ? en : id;
    }

    return { locale, t, setLocale, toggleLocale, tLegacy };
}
```

- [ ] **Step 2: Audit existing callers — verify `t(id, en)` shape still works**

Grep all current callers:

```bash
rtk grep "useLocale" resources/js
```

For each caller using `const { t } = useLocale(); ... t('Foo','Bar')` — these will break because new `t` is vue-i18n's `t(key)`. Replace those calls with `tLegacy('Foo','Bar')` in this same step.

Affected files (from earlier exploration):
- `resources/js/Components/AppNavbar.vue` — has `t('Harga', 'Pricing')`, `t('Masuk', 'Login')`, `t('Daftar', 'Register')`
- `resources/js/Pages/Auth/Login.vue`
- `resources/js/Pages/Auth/Register.vue`
- `resources/js/Pages/Templates/Gallery.vue`
- `resources/js/Components/templates/TemplatePreviewModal.vue`
- `resources/js/Pages/Dashboard/Checklist/Index.vue` (will be migrated to keys in Task 9, but interim must use tLegacy)

For each file, replace the destructure:

```js
// BEFORE
const { locale, toggleLocale, t } = useLocale();
// ... t('Harga', 'Pricing')

// AFTER
const { locale, toggleLocale, tLegacy } = useLocale();
// ... tLegacy('Harga', 'Pricing')
```

Use `Edit` with `replace_all` per file for the destructure line, then individually replace `t(` → `tLegacy(` only in template/script string-pair calls (be careful not to clobber unrelated `t(` usage).

- [ ] **Step 3: Build to verify nothing broken**

```bash
rtk npm run build
```

Expected: build succeeds.

- [ ] **Step 4: Manual smoke test**

```bash
php artisan serve
```

Open `/login` and `/register` in browser. Verify:
- Page loads without JS errors in console
- Toggle button in AppNavbar still flips ID/EN labels (`Masuk` ↔ `Login` etc.)

- [ ] **Step 5: Commit**

```bash
rtk git add resources/js/Composables/useLocale.js resources/js/Components/AppNavbar.vue resources/js/Pages/Auth/Login.vue resources/js/Pages/Auth/Register.vue resources/js/Pages/Templates/Gallery.vue resources/js/Components/templates/TemplatePreviewModal.vue resources/js/Pages/Dashboard/Checklist/Index.vue
rtk git commit -m "feat(i18n): refactor useLocale to wrap vue-i18n, add tLegacy backward compat"
```

---

## Task 7: Create LanguageSwitcher component + mount in DashboardLayout

Extract the toggle button into a reusable component, mount in `DashboardLayout` topbar (next to user menu / collapse button — verify exact location during impl).

**Files:**
- Create: `resources/js/Components/LanguageSwitcher.vue`
- Modify: `resources/js/Layouts/DashboardLayout.vue`
- Modify: `resources/js/Components/AppNavbar.vue` (replace inline toggle with `<LanguageSwitcher />`)

- [ ] **Step 1: Create LanguageSwitcher**

```vue
<script setup>
import { useLocale } from '@/Composables/useLocale';

const { locale, toggleLocale } = useLocale();
</script>

<template>
    <button
        type="button"
        @click="toggleLocale"
        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border text-xs font-semibold transition-all hover:bg-stone-50"
        style="border-color: rgba(146,168,156,0.4); color: #73877C"
        :aria-label="locale === 'id' ? 'Switch to English' : 'Ganti ke Indonesia'"
    >
        <span>{{ locale === 'id' ? '🇮🇩' : '🇬🇧' }}</span>
        <span>{{ locale === 'id' ? 'ID' : 'EN' }}</span>
    </button>
</template>
```

- [ ] **Step 2: Replace inline toggle in `AppNavbar.vue`**

Find the existing `<button @click="toggleLocale" ...>` block (lines ~39-47) and replace with:

```vue
<LanguageSwitcher />
```

Add import at top of `<script setup>`:

```js
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
```

Remove `toggleLocale` from useLocale destructure if no longer used elsewhere in this file. Keep `tLegacy` if still used.

- [ ] **Step 3: Mount in `DashboardLayout.vue`**

Open `resources/js/Layouts/DashboardLayout.vue`. Locate the topbar / header area (search for the section that renders user info or sidebar collapse button — typically near the top of `<template>` after the sidebar block).

Add import in `<script setup>`:

```js
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
```

Mount in topbar template (place before the user menu / avatar):

```vue
<LanguageSwitcher class="mr-2" />
```

Exact placement: in the topbar `<header>` flex row, alongside notification bell / user menu. Find the closing of any logo/title block and the start of right-aligned actions; insert there.

- [ ] **Step 4: Smoke test in browser**

```bash
rtk npm run build
php artisan serve
```

Open `/dashboard`. Verify:
- LanguageSwitcher button visible in topbar
- Click toggles between 🇮🇩 ID and 🇬🇧 EN
- localStorage `theday_lang` updates
- Network tab shows Inertia partial-reload request with `X-Inertia-Partial-Data: translations,locale`
- Sidebar labels still hardcoded ID (will migrate Task 8)

- [ ] **Step 5: Commit**

```bash
rtk git add resources/js/Components/LanguageSwitcher.vue resources/js/Components/AppNavbar.vue resources/js/Layouts/DashboardLayout.vue
rtk git commit -m "feat(i18n): add LanguageSwitcher component, mount in DashboardLayout"
```

---

## Per-Page Migration Workflow

The following Tasks 8 through 18 apply this same workflow per page. Each page task is self-contained — repeat the workflow rather than reference previous tasks.

**Workflow:**

1. **Add namespace block to `lang/id.json`** under `dashboard.<pageNamespace>` with all string keys for that page.
2. **Add equivalent block to `lang/en.json`** with English translations (1-to-1 keys).
3. **Run `node scripts/lang-check.js`** — must exit 0 before proceeding.
4. **Modify `.vue` file:**
   - Add `import { useLocale } from '@/Composables/useLocale'` if missing.
   - Add `const { t } = useLocale();` to `<script setup>` (use `useI18n` directly if file already has `tLegacy` to avoid name collision — alternative: `const { t: tr } = useLocale()` + `tr(...)`).
   - Replace each hardcoded ID string with `{{ t('dashboard.<pageNamespace>.<key>') }}` in template, or `t('dashboard.<pageNamespace>.<key>')` in script.
   - For string interpolation use `{ name: value }` arg: `t('dashboard.index.welcome', { name: user.name })`.
   - For pluralization use vue-i18n syntax: `tc('dashboard.guests.count', n, { n })` (only if needed).
5. **Build:** `rtk npm run build`. Must succeed.
6. **Manual QA:** open page in browser, toggle EN/ID, verify all UI text changes correctly. Watch for raw key paths (`dashboard.foo.bar`) which signal missing keys.
7. **Run lang-check again:** `node scripts/lang-check.js` exit 0.
8. **Commit** with conventional prefix `feat(i18n): migrate <page name> to translation keys`.

**Naming convention:**
- Page namespace = camelCase of route segment, e.g. `BudgetPlanner` → `dashboard.budget`, `BukuTamu` → `dashboard.bukuTamu`, `GuestList` → `dashboard.guests`.
- Within a page, group keys by section: `dashboard.checklist.header.title`, `dashboard.checklist.empty.message`, `dashboard.checklist.actions.add`, etc.

---

## Task 8: Migrate DashboardLayout sidebar nav labels

**Files:**
- Modify: `resources/js/Layouts/DashboardLayout.vue`

- [ ] **Step 1: Verify nav keys exist in lang JSON**

Check Task 2 — `nav.dashboard`, `nav.myInvitations`, `nav.guests`, `nav.guestList`, `nav.rsvp`, `nav.ucapan`, `nav.weddingPlanner`, `nav.budgetPlanner`, `nav.templates`, `nav.paket`, `nav.transactions`, `nav.settings` should already exist in both `lang/id.json` and `lang/en.json`. Confirm with:

```bash
node scripts/lang-check.js
```

- [ ] **Step 2: Refactor `navItems` to use translation keys**

In `DashboardLayout.vue`, change the static `navItems` array. Replace string `label` literals with translation key strings, then use `t()` at render time.

Add to `<script setup>`:

```js
import { useLocale } from '@/Composables/useLocale';
const { t } = useLocale();
```

Update `navItems` definition (replace the literal labels):

```js
const navItems = computed(() => [
    {
        label: t('nav.dashboard'),
        route: 'dashboard',
        icon: `...`, // unchanged
    },
    {
        label: t('nav.myInvitations'),
        route: 'dashboard.invitations.index',
        activePattern: 'dashboard.invitations.*',
        icon: `...`,
    },
    {
        label: t('nav.guests'),
        group: true,
        icon: `...`,
        children: [
            { label: t('nav.guestList'), route: 'dashboard.guest-list.index', activePattern: 'dashboard.guest-list.*', premiumOnly: true },
            { label: t('nav.rsvp'), route: 'dashboard.rsvp.index', activePattern: 'dashboard.rsvp.*' },
            { label: t('nav.ucapan'), route: 'dashboard.buku-tamu.index', activePattern: 'dashboard.buku-tamu.*' },
        ],
    },
    {
        label: t('nav.weddingPlanner'),
        route: 'dashboard.checklist.index',
        icon: `...`,
    },
    {
        label: t('nav.budgetPlanner'),
        route: 'dashboard.budget-planner.index',
        icon: `...`,
    },
    {
        label: t('nav.templates'),
        route: 'dashboard.templates',
        icon: `...`,
    },
    {
        label: t('nav.paket'),
        route: 'dashboard.paket',
        icon: `...`,
    },
    {
        label: t('nav.transactions'),
        route: 'dashboard.transactions.index',
        icon: `...`,
    },
    {
        label: t('nav.settings'),
        route: 'profile.edit',
        icon: `...`,
    },
]);
```

(Keep the existing `icon` SVG path strings — only the `label` field changes. The whole array becomes a `computed` so labels react to locale change.)

- [ ] **Step 3: Update template usage of `navItems`**

Anywhere in `<template>` that iterates `navItems` and accesses `.label` — confirm it works with the computed (Vue auto-unwraps `.value` in template). No template change needed if iteration was `v-for="item in navItems"`.

- [ ] **Step 4: Migrate any other hardcoded ID strings in DashboardLayout**

Grep DashboardLayout.vue for hardcoded ID labels (banners, modal text, button labels). For each, add a key under `dashboard.layout.*` in both lang files, and replace inline.

Examples to look for: expiry banner messages, grace period messages, sidebar collapse button aria-labels, "Buat Undangan", "Upgrade", limit modal text, etc.

For each: add key, add translation, replace usage. Run `node scripts/lang-check.js` after each batch.

- [ ] **Step 5: Build + smoke test**

```bash
rtk npm run build
php artisan serve
```

Open `/dashboard`. Toggle EN/ID. Verify:
- Sidebar nav labels translate
- Banners translate
- No raw `nav.xxx` keys visible

- [ ] **Step 6: Commit**

```bash
rtk git add resources/js/Layouts/DashboardLayout.vue lang/en.json lang/id.json
rtk git commit -m "feat(i18n): migrate DashboardLayout sidebar and banners to translation keys"
```

---

## Task 9: Migrate Dashboard/Checklist/Index.vue

**Files:**
- Modify: `resources/js/Pages/Dashboard/Checklist/Index.vue`
- Modify: `lang/en.json`, `lang/id.json` (add `dashboard.checklist.*`)

- [ ] **Step 1: Inventory strings**

Read the file. Build a flat list of every Indonesian-language string in `<template>` and any user-facing string in `<script>` (placeholder, alert, toast). Group them by UI section (header, filters, list item, empty state, modal, actions).

- [ ] **Step 2: Add namespace block to `lang/id.json` under `dashboard.checklist`**

Replace `"checklist": {}` with the actual key tree, e.g.:

```json
"checklist": {
  "title": "Wedding Checklist",
  "subtitle": "Daftar tugas pernikahan Anda",
  "addTask": "Tambah Tugas",
  "filters": {
    "all": "Semua",
    "todo": "Belum Selesai",
    "done": "Selesai"
  },
  "empty": {
    "title": "Belum ada tugas",
    "description": "Mulai tambahkan checklist pernikahan Anda"
  },
  "actions": {
    "complete": "Tandai Selesai",
    "edit": "Edit",
    "delete": "Hapus"
  }
}
```

(Use the actual strings inventoried in step 1, not these examples.)

- [ ] **Step 3: Add equivalent block to `lang/en.json`**

```json
"checklist": {
  "title": "Wedding Checklist",
  "subtitle": "Your wedding task list",
  "addTask": "Add Task",
  "filters": {
    "all": "All",
    "todo": "To Do",
    "done": "Done"
  },
  "empty": {
    "title": "No tasks yet",
    "description": "Start adding your wedding checklist"
  },
  "actions": {
    "complete": "Mark Complete",
    "edit": "Edit",
    "delete": "Delete"
  }
}
```

- [ ] **Step 4: Run lang-check**

```bash
node scripts/lang-check.js
```

Expected: exit 0.

- [ ] **Step 5: Update `.vue` file**

The file already imports `useLocale` (uses `tLegacy` after Task 6). Add fresh `t` destructure:

```js
const { t, tLegacy } = useLocale();
```

In template, replace each hardcoded string with `{{ t('dashboard.checklist.<key>') }}`. In script, replace alerts/toasts/placeholders with `t('dashboard.checklist.<key>')`. Remove any remaining `tLegacy(...)` calls in this file.

- [ ] **Step 6: Build**

```bash
rtk npm run build
```

Expected: build succeeds, no missing-key warnings (we've disabled them in i18n config but vue-i18n compile might warn).

- [ ] **Step 7: Manual QA**

```bash
php artisan serve
```

Open `/dashboard/checklist`. Toggle EN/ID. Verify every label, button, placeholder, and toast translates. Hunt for raw `dashboard.checklist.*` keys (= missing translation).

- [ ] **Step 8: Commit**

```bash
rtk git add resources/js/Pages/Dashboard/Checklist/Index.vue lang/en.json lang/id.json
rtk git commit -m "feat(i18n): migrate Dashboard/Checklist to translation keys"
```

---

## Task 10: Migrate Dashboard/BudgetPlanner/Index.vue

**Files:**
- Modify: `resources/js/Pages/Dashboard/BudgetPlanner/Index.vue`
- Modify: `lang/en.json`, `lang/id.json` (add under `dashboard.budget`)

Apply the **Per-Page Migration Workflow** to this file.

- [ ] **Step 1: Inventory strings** in `BudgetPlanner/Index.vue` — group by UI section.
- [ ] **Step 2: Add `dashboard.budget.*` block to `lang/id.json`** with all keys (use real strings from file).
- [ ] **Step 3: Add equivalent EN block to `lang/en.json`**.
- [ ] **Step 4: Run `node scripts/lang-check.js`** — must exit 0.
- [ ] **Step 5: In the `.vue` file**, add `const { t } = useLocale();` (with `import { useLocale } from '@/Composables/useLocale';` if missing). Replace all hardcoded ID strings in `<template>` and `<script>` with `t('dashboard.budget.<key>')`.
- [ ] **Step 6: Build:** `rtk npm run build`.
- [ ] **Step 7: Manual QA** — open `/dashboard/budget-planner`, toggle EN/ID, verify all text translates, no raw keys visible.
- [ ] **Step 8: Commit:**

```bash
rtk git add resources/js/Pages/Dashboard/BudgetPlanner/Index.vue lang/en.json lang/id.json
rtk git commit -m "feat(i18n): migrate Dashboard/BudgetPlanner to translation keys"
```

---

## Task 11: Migrate Dashboard/BukuTamu/Index.vue

**Files:**
- Modify: `resources/js/Pages/Dashboard/BukuTamu/Index.vue`
- Modify: `lang/en.json`, `lang/id.json` (add under `dashboard.bukuTamu`)

Apply the **Per-Page Migration Workflow** to this file.

- [ ] **Step 1: Inventory strings.**
- [ ] **Step 2: Add `dashboard.bukuTamu.*` to `lang/id.json`.**
- [ ] **Step 3: Add equivalent to `lang/en.json`.**
- [ ] **Step 4: `node scripts/lang-check.js` → exit 0.**
- [ ] **Step 5: Replace strings in `.vue` with `t('dashboard.bukuTamu.<key>')`.**
- [ ] **Step 6: `rtk npm run build`.**
- [ ] **Step 7: QA `/dashboard/buku-tamu`.**
- [ ] **Step 8: Commit:**

```bash
rtk git add resources/js/Pages/Dashboard/BukuTamu/Index.vue lang/en.json lang/id.json
rtk git commit -m "feat(i18n): migrate Dashboard/BukuTamu to translation keys"
```

---

## Task 12: Migrate Dashboard/GuestList/Index.vue (if file exists)

**Files:**
- Modify: `resources/js/Pages/Dashboard/GuestList/Index.vue` (verify existence first)
- Modify: `lang/en.json`, `lang/id.json` (add under `dashboard.guests`)

- [ ] **Step 0: Verify file exists**

```bash
ls resources/js/Pages/Dashboard/GuestList/Index.vue
```

If file does NOT exist, mark this task complete with "skipped — file absent" in commit log and move on.

If exists, apply **Per-Page Migration Workflow:**

- [ ] **Step 1: Inventory strings.**
- [ ] **Step 2: Add `dashboard.guests.*` to `lang/id.json`.**
- [ ] **Step 3: Add equivalent to `lang/en.json`.**
- [ ] **Step 4: `node scripts/lang-check.js` → exit 0.**
- [ ] **Step 5: Replace strings with `t('dashboard.guests.<key>')`.**
- [ ] **Step 6: `rtk npm run build`.**
- [ ] **Step 7: QA `/dashboard/guest-list`.**
- [ ] **Step 8: Commit:**

```bash
rtk git add resources/js/Pages/Dashboard/GuestList/Index.vue lang/en.json lang/id.json
rtk git commit -m "feat(i18n): migrate Dashboard/GuestList to translation keys"
```

---

## Task 13: Migrate Dashboard/Rsvp (Index.vue + Show.vue)

**Files:**
- Modify: `resources/js/Pages/Dashboard/Rsvp/Index.vue`
- Modify: `resources/js/Pages/Dashboard/Rsvp/Show.vue`
- Modify: `lang/en.json`, `lang/id.json` (add under `dashboard.rsvp`)

Apply workflow to BOTH files in same task — they share the same namespace.

- [ ] **Step 1: Inventory strings from BOTH `Index.vue` and `Show.vue`.** Group by file (`dashboard.rsvp.list.*` vs `dashboard.rsvp.detail.*`).
- [ ] **Step 2: Add `dashboard.rsvp.*` to `lang/id.json`** with both subsections.
- [ ] **Step 3: Add equivalent to `lang/en.json`.**
- [ ] **Step 4: `node scripts/lang-check.js` → exit 0.**
- [ ] **Step 5: Replace strings in BOTH files with `t('dashboard.rsvp.<section>.<key>')`.**
- [ ] **Step 6: `rtk npm run build`.**
- [ ] **Step 7: QA `/dashboard/rsvp` AND `/dashboard/rsvp/{id}`.**
- [ ] **Step 8: Commit:**

```bash
rtk git add resources/js/Pages/Dashboard/Rsvp lang/en.json lang/id.json
rtk git commit -m "feat(i18n): migrate Dashboard/Rsvp (index+show) to translation keys"
```

---

## Task 14: Migrate Dashboard/Transactions/Index.vue

**Files:**
- Modify: `resources/js/Pages/Dashboard/Transactions/Index.vue`
- Modify: `lang/en.json`, `lang/id.json` (add under `dashboard.transactions`)

Apply workflow.

- [ ] **Step 1: Inventory strings.**
- [ ] **Step 2: Add `dashboard.transactions.*` to `lang/id.json`.**
- [ ] **Step 3: Add equivalent to `lang/en.json`.**
- [ ] **Step 4: `node scripts/lang-check.js` → exit 0.**
- [ ] **Step 5: Replace strings with `t('dashboard.transactions.<key>')`.**
- [ ] **Step 6: `rtk npm run build`.**
- [ ] **Step 7: QA `/dashboard/transactions`.**
- [ ] **Step 8: Commit:**

```bash
rtk git add resources/js/Pages/Dashboard/Transactions/Index.vue lang/en.json lang/id.json
rtk git commit -m "feat(i18n): migrate Dashboard/Transactions to translation keys"
```

---

## Task 15: Migrate Dashboard/Invitations (Index.vue + Create.vue + Customize.vue)

**Files:**
- Modify: `resources/js/Pages/Dashboard/Invitations/Index.vue`
- Modify: `resources/js/Pages/Dashboard/Invitations/Create.vue`
- Modify: `resources/js/Pages/Dashboard/Invitations/Customize.vue`
- Modify: `lang/en.json`, `lang/id.json` (add under `dashboard.invitations`)

Apply workflow to all three files. Shared namespace `dashboard.invitations.*` with subsections `list`, `create`, `customize`.

**Note:** `Customize.vue` is a complex page with section editors. Strings inside the section editor sub-components (`SectionGiftEditor`, `SectionCoverEditor` etc) are OUT OF SCOPE for this task — they belong to the public invitation editor and use their own data model. Only translate top-level Customize.vue chrome (page header, sidebar nav, save button, etc).

- [ ] **Step 1: Inventory strings from all 3 files** (only top-level chrome of Customize.vue).
- [ ] **Step 2: Add `dashboard.invitations.*` to `lang/id.json`** with `list`, `create`, `customize` subsections.
- [ ] **Step 3: Add equivalent to `lang/en.json`.**
- [ ] **Step 4: `node scripts/lang-check.js` → exit 0.**
- [ ] **Step 5: Replace strings with `t('dashboard.invitations.<section>.<key>')`.**
- [ ] **Step 6: `rtk npm run build`.**
- [ ] **Step 7: QA `/dashboard/invitations`, `/dashboard/invitations/create`, `/dashboard/invitations/{id}/customize`.**
- [ ] **Step 8: Commit:**

```bash
rtk git add resources/js/Pages/Dashboard/Invitations lang/en.json lang/id.json
rtk git commit -m "feat(i18n): migrate Dashboard/Invitations (list+create+customize chrome) to translation keys"
```

---

## Task 16: Migrate Dashboard/Paket.vue

**Files:**
- Modify: `resources/js/Pages/Dashboard/Paket.vue`
- Modify: `lang/en.json`, `lang/id.json` (add under `dashboard.paket`)

Apply workflow.

- [ ] **Step 1: Inventory strings** (~60 keys expected).
- [ ] **Step 2: Add `dashboard.paket.*` to `lang/id.json`.**
- [ ] **Step 3: Add equivalent to `lang/en.json`.**
- [ ] **Step 4: `node scripts/lang-check.js` → exit 0.**
- [ ] **Step 5: Replace strings with `t('dashboard.paket.<key>')`.**

  **Special note on plan names/features:** plan names from backend (`Free`, `Premium`) come from `plans` table — do NOT translate those (they are data, not UI). Only translate UI labels around them: section headings, comparison table headers, CTA buttons, FAQ.

- [ ] **Step 6: `rtk npm run build`.**
- [ ] **Step 7: QA `/dashboard/paket`.**
- [ ] **Step 8: Commit:**

```bash
rtk git add resources/js/Pages/Dashboard/Paket.vue lang/en.json lang/id.json
rtk git commit -m "feat(i18n): migrate Dashboard/Paket to translation keys"
```

---

## Task 17: Migrate Dashboard/Templates.vue

**Files:**
- Modify: `resources/js/Pages/Dashboard/Templates.vue`
- Modify: `lang/en.json`, `lang/id.json` (add under `dashboard.templates`)

Apply workflow.

- [ ] **Step 1: Inventory strings** (~80 keys expected).
- [ ] **Step 2: Add `dashboard.templates.*` to `lang/id.json`** — likely subsections: `header`, `filters`, `card`, `preview`, `apply`, `empty`.
- [ ] **Step 3: Add equivalent to `lang/en.json`.**
- [ ] **Step 4: `node scripts/lang-check.js` → exit 0.**
- [ ] **Step 5: Replace strings with `t('dashboard.templates.<section>.<key>')`.**

  **Note:** template names/descriptions come from backend — do NOT translate.

- [ ] **Step 6: `rtk npm run build`.**
- [ ] **Step 7: QA `/dashboard/templates`.**
- [ ] **Step 8: Commit:**

```bash
rtk git add resources/js/Pages/Dashboard/Templates.vue lang/en.json lang/id.json
rtk git commit -m "feat(i18n): migrate Dashboard/Templates to translation keys"
```

---

## Task 18: Migrate Dashboard/Index.vue (largest, last)

**Files:**
- Modify: `resources/js/Pages/Dashboard/Index.vue` (37.6K — largest dashboard file)
- Modify: `lang/en.json`, `lang/id.json` (add under `dashboard.index`)

Apply workflow with extra care — file is large, expect ~100+ keys.

- [ ] **Step 1: Inventory strings.** Group by section: `header`, `welcome`, `quotaCard`, `quickActions`, `recentInvitations`, `analyticsCard`, `upgradeCard`, `tips`, `emptyState`, etc.
- [ ] **Step 2: Add `dashboard.index.*` to `lang/id.json`** with subsections.
- [ ] **Step 3: Add equivalent to `lang/en.json`.** Pay attention to interpolated strings like welcome message — use `{name}` syntax.

  Example:
  ```json
  "welcome": "Selamat datang kembali, {name}"
  ```
  Used as: `t('dashboard.index.welcome', { name: user.name })`.

- [ ] **Step 4: `node scripts/lang-check.js` → exit 0.**
- [ ] **Step 5: Replace strings with `t('dashboard.index.<section>.<key>')`.**

  Work in chunks: header section first, build, QA, commit; then next section, build, QA, commit. Avoid one mega-commit.

- [ ] **Step 6: `rtk npm run build`** after each chunk.
- [ ] **Step 7: QA `/dashboard`** after each chunk. Toggle EN/ID, verify changes.
- [ ] **Step 8: Final commit** (or per-chunk commits with same prefix):

```bash
rtk git add resources/js/Pages/Dashboard/Index.vue lang/en.json lang/id.json
rtk git commit -m "feat(i18n): migrate Dashboard/Index to translation keys"
```

---

## Task 19: Final verification + composer script alias

**Files:**
- Modify: `composer.json` (optional — add alias)

- [ ] **Step 1: Run all backend tests**

```bash
php artisan test
```

Expected: all pass. Especially `InertiaTranslationsShareTest` from Task 4.

- [ ] **Step 2: Run lang-check**

```bash
node scripts/lang-check.js
```

Expected: exit 0 with key count printed.

- [ ] **Step 3: Build production bundle**

```bash
rtk npm run build
```

Expected: success, no warnings about missing translations.

- [ ] **Step 4: Full-flow manual QA**

Start `php artisan serve`. Login. Navigate every dashboard page. Toggle EN/ID at each. Verify:
- Toggle button works on every page
- All visible UI text translates
- localStorage persists choice across page reloads
- Refresh page → locale preserved
- Form validations (if user-facing) translate (server-side messages from `validation.*`)
- No raw `dashboard.*.*` key paths visible in UI
- Login/Register/Templates Gallery/AppNavbar still work (legacy `tLegacy` callers)

- [ ] **Step 5: Add composer script alias (optional convenience)**

In `composer.json`, under `"scripts"`, add:

```json
"lang-check": "node scripts/lang-check.js"
```

Then verify:

```bash
composer lang-check
```

Expected: same output as `node scripts/lang-check.js`.

- [ ] **Step 6: Final commit + push**

```bash
rtk git add composer.json
rtk git commit -m "chore(i18n): add composer lang-check alias"
rtk git push -u origin multi-lang
```

(Push only if user confirms — the user is the gatekeeper for remote pushes per CLAUDE.md guidance.)

---

## Self-Review Notes

**Spec coverage check:**

| Spec section | Implementing task |
|--------------|-------------------|
| Single source of truth (`lang/{en,id}.json`) | Task 2 |
| Inertia middleware sharing | Task 4 |
| vue-i18n setup | Tasks 1, 5 |
| useLocale refactor + tLegacy | Task 6 |
| LanguageSwitcher in dashboard | Task 7 |
| Sidebar/nav migration | Task 8 |
| 10 dashboard page migrations | Tasks 9–18 |
| Lang-check script | Task 3 |
| Middleware integration test | Task 4 |
| Final QA | Task 19 |

All spec requirements have implementing tasks.

**Type/identifier consistency check:**

- `useLocale` returns `{ locale, t, setLocale, toggleLocale, tLegacy }` — same shape across Tasks 6, 7, 8, 9–18.
- vue-i18n key syntax `t('dashboard.<page>.<key>')` consistent.
- `lang_path("{$locale}.json")` consistent.
- Inertia shared prop names `locale` and `translations` consistent (Tasks 4, 5).

No drift found.

**Risks reminder:**

- Task 6 `t(` → `tLegacy(` substitution needs care. Use file-by-file Edit (not blind replace_all) to avoid clobbering unrelated `t(` calls. Verify each file's call sites manually.
- Task 18 (Dashboard/Index.vue) is the longest — break into chunks per UI section.

---

## Execution Handoff

Plan complete and saved to `docs/superpowers/plans/2026-05-15-dashboard-i18n-en.md`. Two execution options:

**1. Subagent-Driven (recommended)** — Dispatch a fresh subagent per task, review between tasks, fast iteration. Good for this plan because each page migration is independent.

**2. Inline Execution** — Execute tasks in this session using `superpowers:executing-plans`, batch with checkpoints for review.
