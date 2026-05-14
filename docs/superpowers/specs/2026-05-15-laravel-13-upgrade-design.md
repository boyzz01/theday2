# Laravel 13 Upgrade Design

**Date:** 2026-05-15
**Branch:** `upgrade/laravel-13`
**Effort:** ~2–3 hours

## Context

Project currently runs Laravel 12 with PHP ^8.2. Laravel 13 released March 17, 2026. Upgrade is motivated by:
- PHP 8.2 EOL December 2026 — PHP 8.4 upgrade is inevitable anyway
- Low upgrade cost: codebase has no deprecated API usage
- Stay on supported Laravel version (L12 enters security-only mode)

## Scope of Changes

### Required

| Item | File | Change |
|---|---|---|
| PHP constraint | `composer.json` | `^8.2` → `^8.4` |
| Laravel framework | `composer.json` | `^12.0` → `^13.0` |
| PHP runtime | Laragon + production server | Install PHP 8.4 |
| Cache/session env vars | `.env` + `.env.example` | Add explicit `CACHE_PREFIX`, `SESSION_COOKIE` |

### No Action Required

| Item | Reason |
|---|---|
| CSRF middleware | Already uses `validateCsrfTokens()` API — not old `VerifyCsrfToken` class |
| `Str::slug()` | All usages are single-arg — not affected by separator deprecation |
| `->paginate()` | All calls use explicit numbers (9, 20, 30) — not affected by default 15→25 change |
| DELETE queries | All are Eloquent model `->delete()`, not raw query builder |
| `Route::controller()` | Not used in codebase |
| `Model::unguard()` | Not used in codebase |
| `$request->has([...])` | Not used in codebase |

### Package Compatibility (all confirmed)

| Package | Current | L13 Compatible |
|---|---|---|
| `inertiajs/inertia-laravel` | ^2.0 | Yes |
| `laravel/sanctum` | ^4.0 | Yes |
| `laravel/socialite` | ^5.26 | Yes |
| `spatie/laravel-medialibrary` | ^11.21 | Yes (v11.22.1 supports ^13.0) |
| `spatie/laravel-permission` | ^6.25 | Yes |
| `maatwebsite/excel` | ^3.1 | Yes (supports ^13.0) |
| `tightenco/ziggy` | ^2.0 | Yes |

## Execution Plan

### Phase 1 — Local (Laragon)

1. Create branch: `git checkout -b upgrade/laravel-13`
2. Upgrade PHP in Laragon to 8.4
3. Update `composer.json`:
   - `"php": "^8.4"`
   - `"laravel/framework": "^13.0"`
4. Run `composer update`
5. Add to `.env` and `.env.example`:
   ```
   CACHE_PREFIX=theday2_cache
   SESSION_COOKIE=theday2_session
   ```
6. Run `php artisan config:clear && php artisan cache:clear`
7. Smoke test locally: login, create invitation, export Excel, upload media

### Phase 2 — Production

8. Upgrade production server PHP to 8.4
9. Push branch, deploy
10. Run `php artisan config:clear && php artisan cache:clear` on server
11. Verify critical paths: Mayar payment webhook, invitation access, auth flow

## Testing Checklist

- [ ] Login / logout
- [ ] Onboarding flow
- [ ] Create & edit invitation
- [ ] Upload media (Spatie medialibrary)
- [ ] Export guest list (Maatwebsite Excel)
- [ ] Mayar payment webhook (`POST /webhooks/mayar`)
- [ ] Invitation public access (`invitation.access` middleware)
- [ ] Admin panel

## Risk Assessment

**Low risk overall.** No deprecated APIs used in codebase. All packages confirmed compatible. Only real risk is PHP 8.4 runtime differences on server — mitigated by testing locally first.
