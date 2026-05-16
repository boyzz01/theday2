# Laravel 13 Upgrade Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Upgrade theday2 from Laravel 12 + PHP 8.2 to Laravel 13 + PHP 8.4 with zero breaking changes.

**Architecture:** Pure dependency bump — no application code changes required. The codebase has no deprecated API usage. Only composer.json constraints, PHP runtime, and two env vars need to change.

**Tech Stack:** Laravel 13, PHP 8.4, Composer, Laragon (local), production server (PHP upgrade required separately)

**Spec:** `docs/superpowers/specs/2026-05-15-laravel-13-upgrade-design.md`

---

## File Structure

| File | Action | Change |
|---|---|---|
| `composer.json` | Modify | PHP `^8.2` → `^8.4`, framework `^12.0` → `^13.0` |
| `.env.example` | Modify | Add `CACHE_PREFIX` and `SESSION_COOKIE` |
| `.env` | Modify (local only, not committed) | Add `CACHE_PREFIX` and `SESSION_COOKIE` |

No PHP application files need to change.

---

### Task 1: Create upgrade branch

**Files:**
- No files changed — git operation only

- [ ] **Step 1: Create and switch to upgrade branch**

```bash
git checkout -b upgrade/laravel-13
```

Expected output: `Switched to a new branch 'upgrade/laravel-13'`

- [ ] **Step 2: Verify branch**

```bash
git branch
```

Expected: `* upgrade/laravel-13` is current branch

---

### Task 2: Upgrade PHP in Laragon to 8.4

**Files:**
- No files changed — runtime configuration

- [ ] **Step 1: In Laragon, switch PHP version**

Open Laragon → right-click tray icon → PHP → 8.4.x. If 8.4 not listed, download via Laragon menu (PHP → switch to another version → download PHP 8.4).

- [ ] **Step 2: Verify PHP version in terminal**

```bash
php -v
```

Expected output starts with: `PHP 8.4.`

- [ ] **Step 3: Verify Composer still works**

```bash
composer --version
```

Expected: Composer version printed without errors.

---

### Task 3: Update composer.json version constraints

**Files:**
- Modify: `composer.json`

- [ ] **Step 1: Update PHP and Laravel framework constraints**

In `composer.json`, change:

```json
"php": "^8.4",
"laravel/framework": "^13.0",
```

The full `require` block should look like:

```json
"require": {
    "php": "^8.4",
    "inertiajs/inertia-laravel": "^2.0",
    "laravel/framework": "^13.0",
    "laravel/sanctum": "^4.0",
    "laravel/socialite": "^5.26",
    "laravel/tinker": "^2.10.1",
    "league/flysystem-aws-s3-v3": "*",
    "maatwebsite/excel": "^3.1",
    "spatie/laravel-medialibrary": "^11.21",
    "spatie/laravel-permission": "^6.25",
    "tightenco/ziggy": "^2.0"
},
```

- [ ] **Step 2: Verify JSON is valid**

```bash
php -r "json_decode(file_get_contents('composer.json')); echo json_last_error() === JSON_ERROR_NONE ? 'valid' : 'invalid';"
```

Expected: `valid`

---

### Task 4: Run composer update

**Files:**
- `composer.lock` — updated automatically

- [ ] **Step 1: Run composer update**

```bash
composer update
```

This will resolve all dependencies to their latest compatible versions for Laravel 13. Expected: no errors. If you see a conflict, note the package name and check its GitHub releases page for a L13-compatible version.

- [ ] **Step 2: Verify Laravel version installed**

```bash
php artisan --version
```

Expected output: `Laravel Framework 13.x.x`

- [ ] **Step 3: Verify PHP version seen by artisan**

```bash
php artisan about | grep PHP
```

Expected: PHP version shows 8.4.x

---

### Task 5: Add explicit cache and session env vars

**Files:**
- Modify: `.env.example`
- Modify: `.env` (local, not committed)

These vars prevent the L13 cache prefix default change from affecting existing sessions/cache keys.

- [ ] **Step 1: Add to `.env.example`**

Find the `CACHE_STORE` line and add below it:

```
CACHE_PREFIX=theday2_cache
SESSION_COOKIE=theday2_session
```

- [ ] **Step 2: Add same vars to local `.env`**

```
CACHE_PREFIX=theday2_cache
SESSION_COOKIE=theday2_session
```

- [ ] **Step 3: Clear all caches**

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

Expected: `Configuration cache cleared!` etc. for each command.

---

### Task 6: Smoke test locally

**Files:**
- No files changed — verification only

Run through each critical path manually in the browser. Start the dev server first:

```bash
php artisan serve
```

And in another terminal:

```bash
npm run dev
```

- [ ] **Login / logout** — verify auth works
- [ ] **Onboarding flow** — create a new test account and complete onboarding
- [ ] **Create invitation** — create a new undangan, fill couple names, save
- [ ] **Edit invitation** — edit an existing undangan, upload a media file (tests Spatie medialibrary)
- [ ] **Guest list export** — export guest list as Excel (tests Maatwebsite Excel)
- [ ] **Invitation public access** — open a public invitation URL (tests `invitation.access` middleware)
- [ ] **Admin panel** — log in as admin, view articles list
- [ ] **Check browser console** — no JS errors on key pages

If any step fails, check `storage/logs/laravel.log` for the error.

- [ ] **Step: Commit after all smoke tests pass**

```bash
git add composer.json composer.lock .env.example
git commit -m "chore: upgrade to Laravel 13 + PHP 8.4"
```

---

### Task 7: Production deployment

**Files:**
- No files changed — deployment steps

- [ ] **Step 1: Upgrade production server PHP to 8.4**

Steps depend on server provider. Common approaches:
- **Laravel Forge:** Site → PHP Version → select 8.4
- **Shared hosting / VPS (Ubuntu):** `sudo apt install php8.4 php8.4-fpm php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-gd php8.4-mysql` then update web server config
- **Ploi / Runcloud:** switch PHP version in server panel

- [ ] **Step 2: Add env vars on production server**

Add to production `.env`:
```
CACHE_PREFIX=theday2_cache
SESSION_COOKIE=theday2_session
```

- [ ] **Step 3: Push branch and merge**

```bash
git push origin upgrade/laravel-13
```

Then merge to main (via PR or direct merge).

- [ ] **Step 4: Deploy**

Run on server after deploy:

```bash
composer install --no-dev --optimize-autoloader
php artisan config:clear
php artisan cache:clear
php artisan route:cache
php artisan view:cache
```

- [ ] **Step 5: Verify production**

Check these endpoints:
- `GET /` — landing page loads
- `POST /webhooks/mayar` — returns 200 (test with curl or check Mayar dashboard)
- Login flow works
- One public invitation URL loads correctly
