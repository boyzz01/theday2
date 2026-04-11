# Project Rules — TheDay

You are working on "TheDay", a Laravel 11 SaaS application for creating premium digital wedding invitations in the Indonesian market.

## CRITICAL: Read SKILL.md First
Before making ANY changes, read the SKILL.md file in the project root. It contains the complete tech stack, architecture rules, naming conventions, and business rules. Follow them strictly.

## CRITICAL: Use RTK for ALL Shell Commands
This project uses RTK (Rust Token Killer) to optimize token usage.
Every shell command MUST be prefixed with `rtk`.

```bash
# ALWAYS do this:
rtk git status
rtk git diff
rtk git log -n 10
rtk php artisan migrate
rtk php artisan test
rtk php artisan make:model ModelName
rtk php artisan make:controller ControllerName
rtk php artisan make:migration create_table_name
rtk php artisan route:list
rtk php artisan tinker
rtk composer require package/name
rtk composer install
rtk npm install
rtk npm run build
rtk find . -name "*.php"
rtk grep -r "pattern" --include="*.php" .
rtk ls -la directory/
rtk cat path/to/file.php

# NEVER do this (wastes tokens):
git status
php artisan migrate
npm run build

# Exceptions (no rtk needed):
cd /path/to/dir
php artisan serve    # long-running
npm run dev          # long-running
```

## Stack
- Laravel 11 + PHP 8.2+
- Inertia.js + Vue 3 (Composition API, <script setup>)
- Tailwind CSS 3
- MySQL 8 with UUID primary keys
- Vite for build

## Auth Flow
Registration-first flow: users must register before accessing the invitation editor.
When an unauthenticated user clicks "Gunakan Template", they are redirected to /register.
After registration/login, the selected template context is restored via session (pending_template)
and the user is taken directly to the invitation editor with that template pre-selected.

## Code Style

### PHP
- Strict types: `declare(strict_types=1);` in every file
- Use PHP 8.1+ features: enums, named arguments, readonly properties, match expressions
- Type hints on all parameters and return types
- Service classes for business logic, controllers stay thin
- Form Request classes for all validation
- Policy classes for all authorization
- Never use `DB::` facade for queries — always use Eloquent
- Always eager load relationships to avoid N+1
- Use `when()` for conditional queries

### Vue / JavaScript
- Always `<script setup>` with Composition API
- Define props with `defineProps<{ }>()` syntax
- Use Inertia `useForm()` for forms, `router.visit()` for navigation
- Composables in `resources/js/Composables/useXxx.js`
- No Pinia — use Inertia shared data + composables
- TailwindCSS only — no custom CSS except for invitation template animations

### General
- No console.log in production code
- No commented-out code in commits
- Descriptive variable names in English
- Comments in English
- User-facing text (UI labels, messages) in Bahasa Indonesia

## File Naming
- Models: `PascalCase.php`
- Controllers: `PascalCaseController.php`
- Vue Components: `PascalCase.vue`
- Composables: `useXxx.js`
- Migrations: Laravel default timestamp format

## Testing
- Feature tests for all API endpoints
- Test happy path + key error cases
- Use factories for test data
- Run `rtk php artisan test` before committing

## Response Format
When generating code:
1. Show the file path as a comment at the top
2. Generate complete, working code — no placeholders or "// TODO"
3. If modifying existing file, show only the changed section with context
4. After generating, suggest the artisan/npm commands to run (WITH rtk prefix)
5. Flag any .env variables that need to be added

## Do NOT
- Run shell commands without `rtk` prefix
- Use auto-increment IDs (always UUID)
- Use Options API in Vue
- Put business logic in controllers
- Use inline validation in controllers (always Form Request)
- Skip error handling on API endpoints
- Hardcode Indonesian text without using a lang file
- Use `localStorage` for important state