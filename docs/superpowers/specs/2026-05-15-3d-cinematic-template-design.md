# 3D Cinematic Walk Template — Design Spec

**Date:** 2026-05-15
**Project:** TheDay — Wedding Invitation Platform
**Stack:** Laravel + Inertia.js + Vue 3 + Vite + TresJS + Three.js

---

## Overview

A premium wedding invitation template featuring a scroll/tap-controlled 3D cinematic camera walk through a wedding venue. As the guest navigates, the camera moves along a predefined path pausing at N stops to reveal invitation info cards. Available in 3 scene variants: Garden, Beach, Chapel.

**Desktop:** invitation rendered inside a phone-frame (~390px) centered on dark background.
**Mobile:** fullscreen, native window scroll (no redundant phone frame).

---

## Design Decisions

| Decision | Choice | Reason |
|----------|--------|--------|
| 3D library | TresJS + Three.js | Vue-native, declarative, reactive |
| Asset format | GLTF/GLB + Draco (geometry) + KTX2/Basis (textures) | Geometry -50%, textures -70% size |
| Navigation driver | Scroll (desktop) / Tap (mobile) | Avoids iOS scroll quirks on mobile |
| Camera path | CatmullRomCurve3 | Smooth interpolation |
| Desktop layout | Phone frame centered, dark bg | Cinematic feel, simplifies viewport |
| Mobile layout | Fullscreen + window scroll | No frame, no bouncy overscroll issues |
| Info cards | HTML Vue overlay (not 3D) | Accessible, easy to style |
| Stops | N-stop array (not hardcoded 4) | Extensible without rewrite |
| SSR | `<ClientOnly>` wrapper around TresCanvas | TresJS crashes server-side |

---

## Entry Gate: "Buka Undangan"

Before the cinematic walk begins, user sees an entry screen:

- Cover photo (blurred), couple name, wedding date
- Single CTA button: **"Buka Undangan"**
- On tap: initializes AudioContext (future-proof for BGM), resets scroll to 0, fades into 3D scene
- This screen doubles as the **loading screen** — GLTF loads in background while user sees this

```
[blurred cover photo]
  Andi & Budi
  12 Juni 2026
  [Buka Undangan ▶]
  [loading bar — fills as GLTF loads]
```

Loading bar shows real progress via `GLTFLoader` progress callback. Button enabled only when load completes. First impression is always the cover photo, never a black frame.

---

## Navigation: Scroll vs Tap

### Desktop
- Scroll on phone-frame container drives `scrollProgress`
- No iOS bounce issues (desktop browser)

### Mobile
- **Tap navigation** (Instagram Story style): tap right half → next stop, tap left half → prev stop
- No scroll at all on mobile — avoids address bar show/hide `clientHeight` jump
- Progress indicator: dot indicators at top (like story progress bar)
- Swipe gesture also supported as progressive enhancement

### Shared composable
```ts
// composables/useCinematicNavigation.ts
const currentStop = ref(0)         // 0 to stops.length-1
const scrollProgress = ref(0)      // 0–1, interpolated between stops
const isMobile = computed(() => window.innerWidth < 768)

// Desktop: listen to container scroll → compute scrollProgress
// Mobile: expose goNext() / goPrev() → animate scrollProgress
```

---

## N-Stop Architecture

Stops are a **data array**, not hardcoded components. Future stops can be added by appending to the array — no code changes needed.

```ts
interface CinematicStop {
  scrollPct: number        // 0–1, position on path
  cameraPos: Vector3
  lookAt: Vector3
  card: {
    component: string      // 'WelcomeCard' | 'StoryCard' | 'EventCard' | 'RsvpCard' | ...
    props: Record<string, unknown>
  }
}
```

**v1 stops (4):**

| # | scrollPct | Location | Card |
|---|-----------|----------|------|
| 0 | 0.00 | Gerbang | WelcomeCard — nama pasangan, tanggal |
| 1 | 0.30 | Taman | StoryCard — kisah pasangan |
| 2 | 0.65 | Aisle | EventCard — waktu, tempat |
| 3 | 1.00 | Altar | RsvpCard — RSVP + pesan doa |

Info card visible when `Math.abs(scrollProgress - stop.scrollPct) < 0.10` (±10%, not ±5%).

**`InfoCard.vue`** is a single component with a `variant` prop that switches layout/content — not 4 separate components.

---

## Component Architecture

```
Invitation/Show.vue
└── <ClientOnly>                          ← SSR safety
    └── CinematicTemplate3D.vue
        ├── EntryGate.vue                 ← loading screen + "Buka Undangan"
        ├── PhoneFrame.vue (desktop only) ← 390px frame, dark bg
        │   ├── TresCanvas
        │   │   ├── CameraRig.vue         ← scrollProgress → curve.getPointAt()
        │   │   │                           + head bob: pos.y += sin(t*80)*0.025
        │   │   ├── SceneEnvironment.vue  ← GLTF per variant
        │   │   └── SceneLighting.vue     ← HDRI + per-variant light config
        │   └── InfoCardOverlay.vue       ← absolute overlay
        │       └── InfoCard.vue          ← variant prop, appears at stops
        ├── MobileProgressDots.vue        ← mobile-only, top story-style dots
        └── ReducedMotionFallback.vue     ← static snapshots, no animation
```

---

## prefers-reduced-motion

If `window.matchMedia('(prefers-reduced-motion: reduce)').matches`:

- Camera does **not** animate along path
- Scene jumps directly to stop on tap/scroll
- Or fallback to full 2D template if WebGL still too intense
- `CameraRig` checks this flag before every frame update

---

## Scene Variants

| Variant | Key | GLTF Contents | Lighting |
|---------|-----|---------------|----------|
| Garden | `garden` | Flower arch, path, hedges, trees | Warm sunlight + HDRI sky |
| Beach | `beach` | Sandy aisle, palms, ocean plane | Golden sunset + env map |
| Chapel | `chapel` | Pews, stained-glass windows, altar, candles | Soft indoor + point lights |

`config.scene_variant` in invitation config. Default: `garden`.

---

## Desktop Layout

```
┌─────────────────────────────────────────────────┐
│           dark bg (#0a0a0a) or blurred cover     │
│    (config.bg_style: 'dark' | 'blurred-cover')  │
│                                                  │
│         ┌─────────────────────┐                  │
│         │   Phone Frame       │                  │
│         │   390 × 844px       │                  │
│         │   border-radius:40px│                  │
│         │                     │                  │
│         │  [TresCanvas]       │                  │
│         │  [InfoOverlay]      │                  │
│         └─────────────────────┘                  │
└─────────────────────────────────────────────────┘
```

Scroll captured on phone-frame container (`overflow-y: scroll`), not `window`.

---

## Camera Details

- Path: `CatmullRomCurve3` through waypoints per scene variant
- **Head bob:** `position.y += Math.sin(t * 80) * 0.025` — walking feel, not drone
- Smooth lerp: `camera.position.lerp(target, 0.05)` per frame for inertia

---

## Performance

| Concern | Mitigation |
|---------|------------|
| GLTF geometry size | Draco compression, target <1MB geometry |
| Texture size | KTX2/Basis compression, -70% vs PNG/JPG |
| Render resolution | `pixelRatio: gpuTier.tier < 2 ? 1 : Math.min(dpr, 2)` via `detect-gpu` |
| Shadows — general | `castShadow` key objects only, `shadowMapSize: 512` |
| Shadows — altar area | `shadowMapSize: 1024` or baked lightmap in GLTF |
| Lazy load | GLTF loaded only on `CinematicTemplate3D` mount |
| WebGL unavailable | Fallback to standard 2D template |

**New dependency:** `detect-gpu` (~5KB) for GPU tier detection before setting pixelRatio.

---

## Open Graph Image

3D scene = no OG preview on WhatsApp share. Solution: **generate static OG image at invitation publish time**.

- Triggered: when invitation status changes to `published`
- Process: PHP/Node snapshot job → render cover photo + couple name + date → save as `og_image`
- Stored: `invitations.og_image_url` (existing or new column)
- Meta tag: `<meta property="og:image" :content="invitation.og_image_url">`
- Fallback: couple's cover photo if OG generation hasn't run yet

OG image generation is a **background job** — does not block publish action.

---

## Deep Linking to Stops

URL hash or query param to jump directly to a stop on load:

- `?stop=rsvp` or `#altar` → on mount, `useCinematicNavigation` reads param → sets `currentStop` to matching stop
- Each stop has an optional `slug` field: `{ scrollPct: 1.0, slug: 'altar', ... }`
- Enables WhatsApp messages: "Klik untuk RSVP: theday.co/i/andi-budi?stop=rsvp"

---

## Integration with Existing System

- New template slug: `cinematic-3d` (row in `templates` table)
- `InvitationRenderer.vue` checks `invitation.template.slug === 'cinematic-3d'` → renders `CinematicTemplate3D` inside `<ClientOnly>`
- `config.scene_variant` field: `garden | beach | chapel` (default `garden`)
- `config.bg_style` field: `dark | blurred-cover` (default `dark`)
- No changes to existing templates or dashboard editor

---

## New Dependencies

```json
"@tresjs/core": "^4.x",
"@tresjs/cientos": "^3.x",
"three": "^0.170.x",
"detect-gpu": "^5.x"
```

Estimated bundle addition: ~360KB gzip, loaded only for this template via dynamic import.

---

## Out of Scope (v1)

- Background music (AudioContext initialized, not used)
- Editor UI for customizing stops or camera path
- User-uploaded 3D models
- Video recording/export of the walk
- More than 3 scene variants
