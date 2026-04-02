# SKILL.md — TheDay (Undangan Digital App)

## Project Identity
- **Name:** TheDay
- **Type:** SaaS web app — undangan digital online
- **Target:** Pernikahan & Ulang Tahun (Indonesia market)
- **URL Pattern:** theday.id/{slug-undangan}

## RTK (Rust Token Killer) — WAJIB
Project ini menggunakan RTK untuk menghemat token saat vibe coding.
Semua shell command HARUS dijalankan dengan prefix `rtk`.

```bash
# BENAR — pakai rtk
rtk git status
rtk git log -n 10
rtk git diff
rtk php artisan migrate
rtk php artisan test
rtk npm run build
rtk composer require package/name
rtk find . -name "*.php" -path "*/Controllers/*"
rtk ls -la app/Models/
rtk cat app/Http/Controllers/InvitationController.php
rtk grep -r "Undangin" --include="*.php" .

# SALAH — tanpa rtk (boros token)
git status
php artisan migrate
npm run build
```

Pengecualian (tidak perlu rtk):
- `cd` (bukan external command)
- `php artisan serve` dan `npm run dev` (long-running process)
- `rtk gain` (sudah rtk command)

## Tech Stack (DO NOT DEVIATE)
| Layer | Technology | Version |
|-------|-----------|---------|
| Backend | Laravel | 11.x |
| PHP | PHP | 8.2+ |
| Frontend | Vue 3 + Inertia.js | Vue 3.4+, Inertia 1.x |
| Styling | Tailwind CSS | 3.x |
| Database | MySQL | 8.x |
| Auth | Laravel Breeze (Vue) | — |
| File Storage | Laravel Storage (S3-compatible) | — |
| Payment | Midtrans Snap | — |
| Realtime | Laravel Echo + Pusher/Soketi | — |
| Image Processing | Intervention Image | 3.x |
| Media Library | Spatie Media Library | 11.x |
| Roles | Spatie Permission | 6.x |
| Build Tool | Vite | — |
| Token Optimizer | RTK (Rust Token Killer) | latest |

## Architecture Rules

### Backend
- **UUID everywhere** — semua model pakai UUID, bukan auto-increment
- **Service pattern** — business logic di `app/Services/`, bukan di controller
- **Action classes** — operasi single-purpose di `app/Actions/` (contoh: `PublishInvitationAction`)
- **Form Requests** — selalu validasi via Form Request class, bukan inline di controller
- **Policies** — authorization selalu via Policy, attach ke model
- **Enums** — gunakan PHP 8.1 backed enums di `app/Enums/`
- **API Resources** — response via `JsonResource` / `ResourceCollection`
- **Eager Loading** — selalu eager load relasi, jangan lazy load di loop
- **Caching** — cache config template & public invitation page

### Frontend
- **Composition API** — selalu pakai `<script setup>`, TIDAK pakai Options API
- **Composables** — reusable logic di `resources/js/Composables/` (format: `useXxx.js`)
- **TypeScript-like** — define props dengan `defineProps`, emit dengan `defineEmits`
- **Inertia patterns** — gunakan `useForm()`, `router.visit()`, `usePage()`
- **Component naming** — PascalCase, prefix domain: `EditorStepBasicInfo.vue`, `DashboardStatCard.vue`
- **No Pinia** — cukup pakai Inertia shared data + composables untuk state management
- **Tailwind only** — tidak ada custom CSS kecuali untuk animasi undangan template

### Database
- **UUID** primary key di semua tabel
- **JSON columns** untuk flexible config (`custom_config`, `default_config`, `features`)
- **Soft deletes** pada: invitations, users
- **Index** pada: slug, status, user_id, invitation_id, created_at
- **Enum columns** gunakan string type di migration, cast ke PHP Enum di model

### File Structure
```
app/
├── Actions/              # PublishInvitationAction, CreatePaymentAction
├── Enums/                # EventType, InvitationStatus, PaymentStatus
├── Http/
│   ├── Controllers/
│   │   ├── Admin/        # AdminDashboardController, AdminTemplateController
│   │   ├── Dashboard/    # DashboardController, InvitationController
│   │   └── Public/       # PublicInvitationController, PublicRsvpController
│   ├── Middleware/        # EnsureUserRole, TrackGuestSession
│   └── Requests/         # StoreInvitationRequest, SubmitRsvpRequest
├── Models/               # All Eloquent models
├── Policies/             # InvitationPolicy, TemplatePolicy
├── Services/             # PaymentService, InvitationService, TemplateService
└── Events/               # GuestMessageSent, RsvpSubmitted

resources/js/
├── Components/
│   ├── ui/               # Button, Modal, Input, Select, Badge, Card
│   ├── auth/             # AuthModal, RegisterForm, LoginForm
│   ├── editor/           # EditorStepper, EditorStepXxx, ColorPicker, FontPicker
│   ├── invitation/       # InvitationCover, InvitationEvents, RsvpForm, GuestBook
│   └── dashboard/        # StatCard, InvitationCard, RecentActivity
├── Composables/          # useInvitationEditor, useTemplatePreview, useCountdown, useAuthWall
├── Layouts/              # DashboardLayout, AdminLayout, PublicLayout
└── Pages/
    ├── Auth/             # Login, Register (Breeze defaults)
    ├── Dashboard/        # Index, Invitations/*, Templates/*, Subscription
    ├── Admin/            # Dashboard, Templates/*, Users/*, Plans/*, Transactions/*
    └── Public/           # Show (invitation page), Password (if protected)
```

## Design System

### Color Palette (Default branding)
```
Primary:     #C8A26B (warm gold — untuk branding app)
Secondary:   #FBF7F0 (cream)
Accent:      #B5C4A8 (sage green)
Dark:        #2C2417
Text Muted:  #6B5D4D
Background:  #FFFCF7
```

### Typography
- **App UI:** DM Sans (clean, modern)
- **Display/Headings:** Cormorant Garamond (elegant serif)
- **Invitation templates:** variable per template — loaded from Google Fonts via config

### Component Patterns
- Buttons: rounded-lg, shadow-sm, transition-all
- Cards: rounded-xl, shadow-md, border border-gray-100
- Inputs: rounded-lg, border-gray-300, focus:ring-2 focus:ring-primary
- Modals: centered, backdrop blur, scale animation
- Toast notifications: top-right, auto-dismiss 5s

## Auth Flow (Value-First)
User bisa buat undangan dulu tanpa register.
Register/login diminta saat user mau simpan atau publish (via AuthWall modal).
Guest data disimpan di tabel guest_drafts, di-convert ke invitation saat auth.

## Naming Conventions

### Routes
```
# Public (tanpa auth)
GET    /                          → Landing page
GET    /{slug}                    → Undangan publik

# Guest-accessible (tanpa auth, dengan TrackGuestSession)
GET    /templates                 → Template gallery
GET    /editor                    → Editor (guest mode)

# Dashboard (auth required)
GET    /dashboard                 → Dashboard/Index
GET    /dashboard/invitations     → Dashboard/Invitations/Index
GET    /dashboard/invitations/{id}/edit → Editor (auth mode)

# Admin
GET    /admin                     → Admin/Dashboard

# API
POST   /api/guest/drafts          → save guest draft
POST   /api/invitations           → store invitation
POST   /api/public/{slug}/rsvp    → submit RSVP
POST   /api/webhooks/midtrans     → payment callback
```

## Business Rules

### Free Plan Limits
- Max 1 undangan aktif
- Max 5 foto gallery
- Tidak bisa upload musik sendiri
- Ada watermark "Dibuat dengan TheDay" di footer undangan
- Tidak ada analytics

### Invitation Lifecycle
```
Draft → Published → Expired
         ↓
      Unpublished (back to draft)
```

## Common Gotchas
1. **Selalu pakai `rtk` prefix** untuk semua shell command saat vibe coding
2. **Inertia shared data** — jangan kirim terlalu banyak data via HandleInertiaRequests
3. **File upload size** — set `upload_max_filesize` min 10MB
4. **Music autoplay** — browser block autoplay, butuh user interaction
5. **Midtrans sandbox** — selalu test di sandbox dulu
6. **Image resize** — selalu resize sebelum simpan
7. **Mobile first** — 90%+ tamu buka undangan dari HP via WhatsApp
8. **Timezone** — set app timezone ke Asia/Jakarta
