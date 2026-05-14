# 3D Cinematic Walk Template — Design Spec

**Date:** 2026-05-15
**Project:** TheDay — Wedding Invitation Platform
**Stack:** Laravel + Inertia.js + Vue 3 + Vite + TresJS + Three.js

---

## Overview

A premium wedding invitation template featuring a scroll-controlled 3D cinematic camera walk through a wedding venue. As the guest scrolls, the camera moves along a predefined path (Gate → Garden → Aisle → Altar), pausing at 4 stops to reveal invitation info cards. Available in 3 scene variants: Garden, Beach, Chapel.

All platforms (desktop and mobile) render the invitation inside a phone-frame viewport (~390px wide). On desktop, the frame is centered with a dark background, creating a cinematic "unboxing" feel.

---

## Design Decisions

| Decision | Choice | Reason |
|----------|--------|--------|
| 3D library | TresJS (Vue 3 wrapper for Three.js) | Vue-native, declarative, reactive |
| Asset format | GLTF/GLB with Draco compression | Best balance of quality vs size |
| Scroll driver | Native scroll position → camera curve | No extra scroll library needed |
| Camera path | CatmullRomCurve3 | Smooth interpolation between waypoints |
| Desktop layout | Phone frame centered, dark bg | Consistent experience, simplifies 3D viewport |
| Info cards | HTML Vue overlay (not 3D) | Easier to style, accessible, no WebGL text |

---

## Camera Path

4 stops along `CatmullRomCurve3`, driven by `scrollY / documentHeight`:

| Stop | Scroll % | Location | Info Card |
|------|----------|----------|-----------|
| 0 | 0% | Gerbang (Gate) | Welcome — nama pasangan, tanggal |
| 1 | 30% | Taman (Garden) | Kisah pasangan |
| 2 | 65% | Aisle | Detail acara (waktu, tempat) |
| 3 | 100% | Altar | RSVP + pesan doa |

Info cards fade in/out based on proximity to each stop (±5% scroll range).

---

## Component Architecture

```
Invitation/Show.vue
└── CinematicTemplate3D.vue          ← new, wraps everything
    ├── PhoneFrame.vue                ← constrains to ~390px, centers on desktop
    │   ├── TresCanvas                ← Three.js scene (fills phone frame)
    │   │   ├── CameraRig.vue         ← scroll position → CatmullRomCurve3 camera
    │   │   ├── SceneEnvironment.vue  ← loads GLTF per variant (garden/beach/chapel)
    │   │   └── AmbientLighting.vue   ← HDRI + directional light per variant
    │   └── InfoCardOverlay.vue       ← absolute HTML overlay inside phone frame
    │       └── InfoCard.vue × 4      ← appear/disappear at scroll stops
    └── ScrollDriver.vue              ← invisible tall div, drives scroll events
```

**Shared state via composable:**
```ts
// composables/useCinematicScroll.ts
const scrollProgress = ref(0)   // 0–1
const activeStop = ref(0)        // 0–3
const isMobile = ref(false)      // window.innerWidth < 768
```

---

## Scene Variants

| Variant | Key | GLTF Contents | Lighting |
|---------|-----|---------------|----------|
| Garden | `garden` | Flower arch, path, hedges, trees | Warm sunlight + HDRI sky |
| Beach | `beach` | Sandy aisle, palm trees, ocean plane | Golden sunset + env map |
| Chapel | `chapel` | Pews, arch windows, altar, candles | Soft indoor + point lights |

Variant is set in invitation config (`config.scene_variant`). Default: `garden`.

---

## Desktop Layout

```
┌─────────────────────────────────────────────────┐
│              dark background (#0a0a0a)           │
│                                                  │
│         ┌─────────────────────┐                  │
│         │   Phone Frame       │                  │
│         │   (~390 × 844px)    │                  │
│         │                     │                  │
│         │   [TresCanvas]      │                  │
│         │   [InfoOverlay]     │                  │
│         │                     │                  │
│         └─────────────────────┘                  │
│                                                  │
└─────────────────────────────────────────────────┘
```

- Phone frame: `width: 390px`, `height: 100vh`, `border-radius: 40px`, subtle shadow
- Background: solid `#0a0a0a` default, or blurred cover photo if `config.bg_style === 'blurred-cover'`
- Scroll is captured on the phone frame container, not `window` directly

---

## Scroll Mechanics

1. `ScrollDriver` renders a tall invisible `div` (height: `500vh`) inside the phone frame
2. Phone frame container has `overflow-y: scroll`
3. `useCinematicScroll` listens to container's `scroll` event, computes `scrollProgress = scrollTop / (scrollHeight - clientHeight)`
4. `CameraRig` reads `scrollProgress`, calls `curve.getPointAt(scrollProgress)` each frame
5. Camera position updates on `useRenderLoop` (TresJS RAF loop)

---

## Info Card Behavior

- Each card tied to a scroll stop (0%, 30%, 65%, 100%)
- Card visible when `Math.abs(scrollProgress - stopAt) < 0.05`
- Transition: `opacity` + `translateY` (CSS), duration 300ms
- Cards contain standard invitation data: `invitation.details` props passed from Laravel

---

## Performance

| Concern | Mitigation |
|---------|------------|
| GLTF size | Draco compression, target <2MB per scene |
| Render resolution | `pixelRatio: Math.min(devicePixelRatio, 2)` |
| Shadows | `castShadow` only on key objects, `shadowMapSize: 512` |
| Lazy load | GLTF loaded only when `CinematicTemplate3D` mounts |
| Fallback | If WebGL unavailable, render standard 2D template |

---

## Integration with Existing System

- New template slug: `cinematic-3d` (stored in `templates` table)
- `InvitationRenderer.vue` checks `invitation.template.slug === 'cinematic-3d'` → renders `CinematicTemplate3D` instead of standard section components
- `config.scene_variant` field added to invitation config (garden/beach/chapel)
- No changes to existing templates or dashboard editor

---

## New Dependencies

```json
"@tresjs/core": "^4.x",
"@tresjs/cientos": "^3.x",
"three": "^0.170.x"
```

Estimated bundle addition: ~350KB gzip (Three.js ~280KB + TresJS ~70KB), loaded only for this template via dynamic import.

---

## Out of Scope

- Editor support for customizing camera path waypoints
- User-uploaded custom 3D models
- Multiple camera paths per scene
- Audio synchronized to camera movement
