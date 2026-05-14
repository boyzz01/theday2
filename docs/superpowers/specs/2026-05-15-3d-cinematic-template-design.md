# 3D Cinematic Walk Template — Design Spec

**Date:** 2026-05-15
**Project:** TheDay — Wedding Invitation Platform
**Stack:** Laravel + Inertia.js + Vue 3 + Vite + TresJS + Three.js

---

## Overview

A premium wedding invitation template featuring a scroll/tap-controlled 3D cinematic camera walk through a wedding venue. As the guest navigates, the camera moves along a predefined path pausing at N stops to reveal invitation info cards. Available in 3 scene variants: Garden, Beach, Chapel.

**Desktop:** invitation rendered inside a phone-frame (~390px) centered on dark background.
**Mobile:** fullscreen, tap navigation (Instagram Story-style).

---

## Design Decisions

| Decision | Choice | Reason |
|----------|--------|--------|
| 3D library | TresJS + Three.js | Vue-native, declarative, reactive |
| Asset format | GLTF/GLB + Draco (geometry) + KTX2/Basis (textures) | Geometry -50%, textures -70% size |
| Navigation — desktop | Scroll on phone-frame container | Clean, no iOS quirks |
| Navigation — mobile | Tap left/right (Instagram Story style) | Avoids address-bar clientHeight jumps |
| Camera path | CatmullRomCurve3 per variant | Smooth interpolation, per-scene waypoints |
| Desktop layout | Phone frame centered, dark bg | Cinematic feel |
| Mobile layout | Fullscreen, no phone frame | No redundant wrapper |
| Info cards | HTML Vue overlay (not 3D) | Accessible, easy to style |
| Stops | N-stop array **per scene variant** | Each variant has own waypoints |
| Mobile animation | GSAP tween, duration 1.8s, power2.inOut, overwrite:true | Cinematic, not teleport |
| Info card trigger — desktop | Range ±10% from stop.scrollPct | Continuous scroll |
| Info card trigger — mobile | `currentStop === stop.index` | Discrete tap nav |
| SSR | `<ClientOnly>` wrapper around `CinematicTemplate3D` | TresJS crashes server-side |
| OG image | Pre-rendered 3D snapshot per variant + Canvas API overlay | No Puppeteer, handles 3D visuals |
| AudioContext | Not created in v1 | Safari warns unused context; defer to v2 |

---

## Entry Gate: "Buka Undangan"

Dual-purpose: loading screen + entry gesture.

```
┌────────────────────────────┐
│  [blurred cover photo]     │
│                            │
│    Andi & Budi             │
│    12 Juni 2026            │
│                            │
│  [ Buka Undangan ▶ ]       │
│  [████████░░░░ 65%]        │  ← loading bar
└────────────────────────────┘
```

- GLTF + KTX2 textures + HDRI all load in background while user sees this screen
- Loading bar aggregates progress from all loaders (weighted: GLTF 60%, textures 30%, HDRI 10%)
- Button enabled only when all assets loaded
- On tap: resets scroll/stop to 0, fades into 3D scene. AudioContext **not** created here (v2)
- First impression is always the cover photo, never a black frame

**Error states:**
- Any loader fails (404, decode error, network drop) → show error UI: "Gagal memuat" + "Coba Lagi" button
- Retry re-triggers all failed loaders
- After 2 failed retries → auto-fallback to standard 2D template with toast notification

---

## First-Time UX Hint

On stop 0, after `EntryGate` fades out, show tap hint for 3 seconds then fade:

```
← tap     tap →
  [ ○ ○ ○ ○ ]    ← progress dots
```

Arrow animation pulses left/right. Dismissed after 3s or first tap. Flag stored in `localStorage('cinematic-hint-seen')` — never shown again after first visit.

---

## Navigation

### Desktop
- `overflow-y: scroll` on phone-frame container
- `useCinematicNavigation` listens to container's `scroll` event
- `scrollProgress = scrollTop / (scrollHeight - clientHeight)`

### Mobile
- Fullscreen, no phone frame
- Tap right half → `goNext()`, tap left half → `goPrev()`
- Swipe: `Math.abs(deltaX) > 50 && deltaTime < 300ms` triggers next/prev
- Progress: `MobileProgressDots` at top — **wrapped in a semi-transparent pill backdrop** to stay visible against any scene color (bright garden sky or dark chapel interior)

### Mobile animation
```ts
const tweenToStop = (targetIdx: number) => {
  gsap.to(scrollProgress, {
    value: stops[targetIdx].scrollPct,
    duration: 1.8,
    ease: 'power2.inOut',
    overwrite: true,   // rapid taps cancel previous tween, never stack
  })
  currentStop.value = targetIdx
}
```

### Composable
```ts
// composables/useCinematicNavigation.ts
const currentStop    = ref(0)    // 0 to stops.length-1
const scrollProgress = ref(0)    // 0–1
// isMobile set in onMounted — never accessed at top-level (window unavailable SSR)
let isMobile = false
onMounted(() => { isMobile = window.innerWidth < 768 })
// Desktop → scroll listener
// Mobile  → goNext() / goPrev() → tweenToStop()
```

---

## N-Stop Architecture (per variant)

Stops are owned by each scene variant — waypoints differ per environment.

```ts
interface CinematicStop {
  index:     number
  slug:      string            // unique per variant — enforced at runtime (dev warning)
  scrollPct: number            // 0–1
  cameraPos: Vector3
  lookAt:    Vector3
  card: {
    component: string          // 'WelcomeCard' | 'StoryCard' | 'EventCard' | 'RsvpCard' | ...
    props:     Record<string, unknown>
  }
}

interface SceneVariant {
  key:   'garden' | 'beach' | 'chapel'
  gltf:  string                // path to .glb
  stops: CinematicStop[]
  lighting: LightingConfig
}
```

**Threshold auto-generated** from stop positions — min-clamped to avoid tiny windows when stops are close together:
```ts
function getThreshold(stops: CinematicStop[], index: number): number {
  const prev = stops[index - 1]?.scrollPct ?? 0
  const next = stops[index + 1]?.scrollPct ?? 1
  const curr = stops[index].scrollPct
  return Math.max(Math.min(curr - prev, next - curr) / 2, 0.05) // min 5% always visible
}
```

**v1 stops per variant (all 4 stops, different cameraPos/lookAt):**

| # | slug | scrollPct | Card |
|---|------|-----------|------|
| 0 | welcome | 0.00 | WelcomeCard — nama pasangan, tanggal |
| 1 | story | 0.30 | StoryCard — kisah pasangan |
| 2 | event | 0.65 | EventCard — waktu, tempat |
| 3 | rsvp | 1.00 | RsvpCard — RSVP + doa |

Beach variant: no "gerbang" stop — stop 0 starts at shoreline entrance.
Slug uniqueness validated at runtime with `console.warn` in dev mode.

**Info card visibility:**
- Desktop: `Math.abs(scrollProgress - stop.scrollPct) < threshold` (continuous)
- Mobile: `currentStop === stop.index` (discrete)

**`InfoCard.vue`**: single component, `variant` prop switches layout. Rendered via `<component :is="resolveCard(stop.card.component)" v-bind="stop.card.props" />` inside `InfoCardOverlay` — not 4 separate components.

---

## Component Architecture

```
Invitation/Show.vue
└── <ClientOnly>
    └── CinematicTemplate3D.vue
        ├── EntryGate.vue                  ← loading + "Buka Undangan" + progress bar
        ├── TapHint.vue                    ← first-time UX hint, localStorage flag
        ├── PhoneFrame.vue (desktop only)  ← 390px centered, config.bg_style
        │   └── SceneContainer.vue         ← shared by desktop (inside frame) + mobile (fullscreen)
        │       ├── TresCanvas
        │       │   ├── CameraRig.vue      ← scrollProgress → curve.getPointAt() + head bob
        │       │   ├── SceneEnvironment.vue ← GLTF per variant, onBeforeUnmount: dispose()
        │       │   └── SceneLighting.vue  ← per-variant HDRI + lights
        │       └── InfoCardOverlay.vue
        │           └── InfoCard.vue       ← variant prop, desktop range / mobile index trigger
        └── MobileProgressDots.vue         ← mobile only, pill backdrop, top of screen
```

---

## Fallback Decision Tree

```
App loads
  └── WebGL supported?
        ├── NO  → render standard 2D template (existing InvitationRenderer)
        └── YES → prefers-reduced-motion?
                    ├── YES → ReducedMotionFallback: static scene snapshots per stop,
                    │         tap/scroll jumps directly (no camera animation), no head bob
                    └── NO  → detect-gpu tier
                                ├── tier 0 (very low) → standard 2D template
                                ├── tier 1 (low)      → simplified scene: no particles,
                                │                        shadows off, pixelRatio 1
                                └── tier 2+ (ok)      → full experience, pixelRatio min(dpr,2)
```

---

## prefers-reduced-motion

`CameraRig` checks `matchMedia('(prefers-reduced-motion: reduce)')` before each frame:
- If true: skip lerp/tween, jump to target position instantly
- Head bob disabled
- Mobile: `tweenToStop` uses `duration: 0`

---

## Scene Variants

| Variant | Key | GLTF Contents | Lighting | Stop 0 start |
|---------|-----|---------------|----------|--------------|
| Garden | `garden` | Flower arch, path, hedges, trees | Warm sunlight + HDRI sky | Garden gate |
| Beach | `beach` | Sandy aisle, palms, ocean plane | Golden sunset + env map | Shoreline |
| Chapel | `chapel` | Pews, stained-glass windows, altar, candles | Soft indoor + point lights | Chapel entrance |

`config.scene_variant`: `garden | beach | chapel`. Default: `garden`.

---

## Desktop Layout

`config.bg_style` is **desktop-only** — mobile is always fullscreen with no outer background.

```
┌─────────────────────────────────────────────────┐
│   bg: #0a0a0a (dark) OR blurred cover photo     │
│   config.bg_style: 'dark' | 'blurred-cover'     │
│                  (desktop only)                  │
│         ┌─────────────────────┐                  │
│         │   Phone Frame       │                  │
│         │   390 × 844px       │                  │
│         │   border-radius:40px│                  │
│         │  [TresCanvas]       │                  │
│         │  [InfoOverlay]      │                  │
│         └─────────────────────┘                  │
└─────────────────────────────────────────────────┘
```

---

## Memory Cleanup

Full disposal pattern in `SceneEnvironment.vue` `onBeforeUnmount`:

```ts
// Dispose all PBR maps on all materials (including multi-material meshes)
scene.traverse(obj => {
  obj.geometry?.dispose()
  const mats = Array.isArray(obj.material) ? obj.material : [obj.material]
  mats.forEach(m => {
    if (!m) return
    Object.values(m).forEach(v => { if (v?.isTexture) v.dispose() })
    m.dispose()
  })
})

// Release GPU context entirely
renderer.dispose()
renderer.forceContextLoss()
```

Without `forceContextLoss()`, GPU memory stays allocated even after `dispose()` on some browsers. Critical for users who preview multiple templates in the editor.

---

## Landscape Orientation (Mobile)

**Decision: lock to portrait.** Landscape with tap-nav and portrait-optimized 3D framing breaks badly and adds complexity out of proportion to benefit.

Implementation:
```css
@media (orientation: landscape) and (max-width: 900px) {
  .cinematic-scene { display: none; }
  .cinematic-rotate-hint { display: flex; } /* "Putar perangkat ke mode portrait" */
}
```

`cinematic-rotate-hint` is a simple full-screen overlay with a rotate icon and brief text. No JS needed.

---

## Camera Details

- Path: `CatmullRomCurve3` through waypoints defined per scene variant
- **Head bob:** `position.y += Math.sin(t * 80) * 0.025` (disabled if reduced-motion)
- Smooth lerp: `camera.position.lerp(target, 0.05)` per frame

---

## Performance & Memory

| Concern | Mitigation |
|---------|------------|
| GLTF geometry | Draco compression, target <1MB |
| Textures | KTX2/Basis compression, -70% |
| Render resolution | `gpuTier.tier < 1 ? 1 : gpuTier.tier === 1 ? 1.5 : Math.min(dpr, 2)` via `detect-gpu` |
| Shadows — general | Key objects only, `shadowMapSize: 512` |
| Shadows — altar close-up | `shadowMapSize: 1024` or baked lightmap |
| Lazy load | Dynamic import, GLTF loads on component mount |
| WebGL unavailable | Fallback to 2D template |
| **Memory cleanup** | `onBeforeUnmount`: full PBR + array material dispose + renderer context loss (see below) |

---

## Open Graph Image

Approach: **pre-rendered 3D snapshot per variant + Canvas API text overlay**.

- 3 base images manually rendered once per variant (1200×630px), stored as static assets
- On invitation publish → background job composes: base image + couple name + date via `node-canvas` + `sharp`
- Stored: `invitations.og_image_url`
- Meta: `<meta property="og:image" :content="invitation.og_image_url">`
- Fallback: couple's cover photo if job hasn't run

No Puppeteer needed. 3D visuals come from static base; only text is dynamic.

**Regeneration trigger:** OG image re-generated (not just on publish) whenever these fields change: `details.groom_name`, `details.bride_name`, `events[0].event_date`, `details.cover_photo_url`. Hook via Laravel model `updated` event on `Invitation`, dispatch `GenerateOgImageJob` only when relevant fields dirty.

---

## Deep Linking to Stops

- URL: `?stop=rsvp` or `#rsvp`
- **Flow with EntryGate:** deep link does NOT skip EntryGate. User sees gate → taps "Buka Undangan" → jumps directly to linked stop (no cinematic walk animation). This preserves the entry gesture + keeps AudioContext future-proof.
- On mount: `useCinematicNavigation` stores deep-link slug, applies it after EntryGate dismisses
- Slug uniqueness enforced at runtime (dev warning), required for deep link to resolve correctly

---

## Integration with Existing System

`InvitationRenderer.vue` already has a hard branch: if `template_slug` found in `TEMPLATE_MAP` → render that component exclusively, otherwise → default section renderer. **Existing templates are completely unaffected.**

Only change needed to existing files:
```js
// registry.js — add one line
import CinematicTemplate3D from './CinematicTemplate3D.vue'
export const TEMPLATE_MAP = {
  // ... existing entries unchanged ...
  'cinematic-3d': CinematicTemplate3D,
}
```

Everything else is new files only:
- New template slug: `cinematic-3d` (row in `templates` table)
- New config fields: `scene_variant` (garden/beach/chapel), `bg_style` (dark/blurred-cover, desktop only)
- TresJS + Three.js loaded via **dynamic import inside `CinematicTemplate3D`** — never bundled with other templates

---

## New Dependencies

```json
"@tresjs/core": "^4.x",
"@tresjs/cientos": "^3.x",
"three": "^0.170.x",
"gsap": "^3.x",
"detect-gpu": "^5.x"
```

~390KB gzip total, loaded only for this template via dynamic import. `gsap` may already be used elsewhere; verify before adding.

---

## Out of Scope (v1)

- Background music / AudioContext (initialized in v2, not v1)
- Dashboard editor for stop/camera customization
- User-uploaded 3D models
- More than 3 scene variants
- Video export
