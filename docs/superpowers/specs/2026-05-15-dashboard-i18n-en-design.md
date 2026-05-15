# Dashboard i18n (English) Design Spec

**Date:** 2026-05-15
**Branch:** `multi-lang`
**Goal:** Add English language support to all dashboard pages, with a single source of truth shared between Laravel backend and Vue frontend.

---

## Goals

- Toggle EN/ID instant di navbar dashboard.
- 10 dashboard pages tampil English saat locale=en.
- Single source of truth utk translation: `lang/en.json` & `lang/id.json` di Laravel root.
- Backend (`__()`, Blade, validation) & frontend (Vue components) baca file translation yang sama.
- Locale persist per-device via localStorage.
- Default locale tetap `id`.
- Backward compat: existing inline `t(id, en)` di Login/Register/Templates Gallery/AppNavbar tetap jalan (migrate nanti).

## Non-Goals

- Auto-detect browser locale.
- Locale persist ke DB users.
- Bahasa selain EN/ID.
- RTL support.
- Translate landing page, public invitation, email templates.
- Migrate legacy `t(id, en)` callers ke key-based di spec ini.

---

## Architecture

### Single source of truth

`lang/en.json` & `lang/id.json` di Laravel root. Backend & frontend baca file sama.

```
lang/
  en.json    ← single source EN (~300-500 keys)
  id.json    ← single source ID
  en/auth.php, passwords.php  ← Laravel auth msg (tetap, default Laravel)
  id/auth.php, passwords.php
```

### Backend flow

`App\Http\Middleware\HandleInertiaRequests` share locale + translations ke Vue tiap request:

```php
public function share(Request $request): array {
  $locale = $request->header('X-Locale')
         ?? $request->cookie('locale')
         ?? config('app.locale');
  app()->setLocale($locale);

  $translations = json_decode(
    file_get_contents(lang_path("{$locale}.json")), true
  ) ?? [];

  return array_merge(parent::share($request), [
    'locale' => $locale,
    'translations' => $translations,
  ]);
}
```

Locale resolve order: `X-Locale` header → cookie → `config('app.locale')` (default `id`).

`__('common.save')` di PHP/Blade tetap jalan — Laravel native support JSON translation files di `lang/{locale}.json`.

### Frontend flow

- Library: `vue-i18n` v9 (composition API, `legacy: false`).
- Init di `resources/js/app.js`. Hydrate `messages` dari Inertia shared props per request.
- `useLocale.js` di-refactor jadi wrapper `vue-i18n` — API tetap (`locale`, `setLocale`, `toggleLocale`), tapi `t()` baru pakai key path: `t('dashboard.checklist.title')`.
- `setLocale(lang)` action: update `i18n.global.locale` + localStorage + axios default header `X-Locale` + `router.reload({ only: ['translations'] })` utk fetch translations baru dari backend.
- Backward compat: helper `tLegacy(id, en)` utk caller existing yang belum di-migrate.

---

## Key Naming Convention

Dot-notation, namespace by feature/page. camelCase utk multi-word.

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
    "confirm": "Confirm"
  },
  "nav": {
    "dashboard": "Dashboard",
    "invitations": "Invitations",
    "templates": "Templates",
    "guests": "Guests",
    "rsvp": "RSVP",
    "checklist": "Checklist",
    "budget": "Budget",
    "transactions": "Transactions",
    "logout": "Logout"
  },
  "dashboard": {
    "index": {
      "title": "Dashboard",
      "welcome": "Welcome back, {name}",
      "quota": {
        "label": "Quota",
        "remaining": "{n} of {total} remaining"
      }
    },
    "paket": { },
    "templates": { },
    "checklist": { },
    "budget": { },
    "guests": { },
    "rsvp": { },
    "transactions": { },
    "bukuTamu": { },
    "invitations": { }
  },
  "validation": {
    "required": "{field} is required",
    "email": "Invalid email"
  }
}
```

**Aturan:**
- Top-level namespace: `common`, `nav`, `dashboard.<page>`, `auth`, `validation`, `errors`.
- Multi-word key camelCase (`bukuTamu`, bukan `buku_tamu`).
- Interpolation pakai `{name}` style (vue-i18n native).
- Pluralization pakai `|` separator: `"guests": "no guests | 1 guest | {n} guests"`.

**File size estimate:** ~300-500 keys total, ~15-25kb per file. Acceptable inline di Inertia props.

---

## Component Changes

### LanguageSwitcher (NEW)

`resources/js/Components/LanguageSwitcher.vue` — standalone toggle button di top navbar, samping avatar.

```vue
<template>
  <button @click="toggleLocale" class="px-3 py-1 rounded text-sm font-medium">
    {{ locale === 'id' ? 'ID' : 'EN' }}
  </button>
</template>

<script setup>
import { useLocale } from '@/Composables/useLocale';
const { locale, toggleLocale } = useLocale();
</script>
```

### Navbar integration

Mount `<LanguageSwitcher />` di:
- `resources/js/Components/AppNavbar.vue` — sebelum avatar dropdown.
- Cek juga: dashboard layout pakai navbar terpisah? Kalau iya, mount juga.

### app.js setup

```js
import { createI18n } from 'vue-i18n';

const i18n = createI18n({
  legacy: false,
  locale: localStorage.getItem('theday_lang') || 'id',
  fallbackLocale: 'id',
  messages: {},
});

createInertiaApp({
  setup({ el, App, props, plugin }) {
    const sharedTranslations = props.initialPage.props.translations;
    const currentLocale = i18n.global.locale.value;
    i18n.global.setLocaleMessage(currentLocale, sharedTranslations);

    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(i18n)
      .mount(el);
  },
});
```

### useLocale.js refactor

```js
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const LANG_KEY = 'theday_lang';

export function useLocale() {
  const { locale, t } = useI18n();

  function setLocale(lang) {
    locale.value = lang;
    localStorage.setItem(LANG_KEY, lang);
    axios.defaults.headers.common['X-Locale'] = lang;
    router.reload({ only: ['translations'] });
  }

  function toggleLocale() {
    setLocale(locale.value === 'id' ? 'en' : 'id');
  }

  // Deprecated — utk caller existing sebelum di-migrate ke t(key)
  function tLegacy(id, en) {
    return locale.value === 'en' ? en : id;
  }

  return { locale, t, setLocale, toggleLocale, tLegacy };
}
```

### Inertia onSuccess hook (translations refresh)

Saat `router.reload({ only: ['translations'] })` selesai, update i18n messages utk locale baru:

```js
router.on('success', (event) => {
  const t = event.detail.page.props.translations;
  if (t) i18n.global.setLocaleMessage(i18n.global.locale.value, t);
});
```

---

## Per-Page Migration Order

Sequential, smallest → largest:

1. Shared nav/sidebar (~20 keys)
2. `Dashboard/Checklist/Index.vue`
3. `Dashboard/BudgetPlanner` (~50 keys)
4. `Dashboard/BukuTamu` (~40 keys)
5. `Dashboard/GuestList` (~60 keys)
6. `Dashboard/Rsvp` (~30 keys)
7. `Dashboard/Transactions` (~30 keys)
8. `Dashboard/Invitations/*` (~80 keys)
9. `Dashboard/Paket.vue` (~60 keys)
10. `Dashboard/Templates.vue` (~80 keys)
11. `Dashboard/Index.vue` 37.6K, biggest (~100 keys)

Per page workflow:
1. Extract semua hardcoded ID string → `lang/id.json`.
2. Translate ke EN → `lang/en.json`.
3. Replace di `.vue` dgn `{{ t('dashboard.<page>.<key>') }}`.
4. Run `node scripts/lang-check.js` — pastikan key consistent.
5. Manual QA: toggle EN/ID, cek semua text berubah, no key path bocor.

---

## Testing Strategy

### Unit
- `useLocale`: `setLocale` update i18n + localStorage + axios header. `toggleLocale` flip id↔en.
- PHPUnit: `HandleInertiaRequests` middleware locale resolve order benar, fallback ke `id` kalau file missing.

### Integration
- Pest: render Inertia page dgn header `X-Locale: en` → assert response props punya `translations` EN.

### Lang consistency script
- `scripts/lang-check.js` — diff keys `en.json` vs `id.json`. Exit 1 kalau mismatch. Run di `composer test` atau pre-commit hook.

### Manual QA
- Tiap page yang sudah migrate: toggle EN/ID, cek semua text berubah, no key bocor (key path muncul di UI = missing key).

---

## Edge Cases

| Case | Handling |
|------|----------|
| Missing key di EN | vue-i18n fallback ke `id` (`fallbackLocale`). |
| Missing key di kedua locale | vue-i18n print key path (visible di UI = bug signal). |
| User toggle saat ada modal/form | locale switch instant, form state preserved (Vue reactive). |
| Locale di localStorage corrupt/invalid | fallback ke `id` di `app.js` init. |
| Inertia partial reload | translations re-share via middleware, i18n update via `setLocaleMessage` di success hook. |
| Backend `__()` di email/validation | tetap baca `lang/{locale}.json` — Laravel native support JSON. |
| New string ditambah saat dev | wajib tambah ke EN & ID. Lang-check script catch yang lupa. |
| Cache busting | Inertia share fresh per request — no stale cache. |

---

## Risks

- **R1: 11 pages migration = banyak string churn.** Mitigation: bertahap per page, satu page satu commit/PR.
- **R2: Bundle size naik (~20-40kb translations inline).** Mitigation: acceptable. Kalau membengkak nanti split per-page chunk.
- **R3: Mistranslation.** Mitigation: review native speaker pass setelah draft otomatis.
- **R4: Inline `t(id, en)` legacy code coexist dgn key-based.** Mitigation: `tLegacy` helper jelas marked deprecated, migrate nanti.

---

## Success Criteria

- Toggle EN/ID di navbar dashboard berfungsi instant.
- Semua 10 dashboard pages tampil EN benar saat locale=en.
- Refresh page → locale persist via localStorage.
- `lang-check.js` exit 0 (semua key consistent antara EN & ID).
- No regression di Login/Register/Templates Gallery/AppNavbar (legacy `tLegacy` jalan).
- `__('common.save')` di PHP/Blade jalan baca `lang/{locale}.json`.
