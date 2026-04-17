# Design System Master File

> **LOGIC:** When building a specific page, first check `design-system/pages/[page-name].md`.
> If that file exists, its rules **override** this Master file.
> If not, strictly follow the rules below.

---

**Project:** TheDay
**Updated:** 2026-04-17
**Category:** Premium Wedding SaaS — Sage Green Theme

---

## Global Rules

### Color Palette

| Role | Hex | Tailwind Token | CSS Variable |
|------|-----|---------------|--------------|
| Primary (Sage) | `#92A89C` | `brand-primary` | `--brand-primary` |
| Primary Hover | `#73877C` | `brand-primary-hover` | `--brand-primary-hover` |
| Primary Soft | `#B8C7BF` | `brand-primary-soft` | `--brand-primary-soft` |
| Premium Accent (Gold) | `#C8A26B` | `brand-premium` | `--brand-premium` |
| Premium Hover | `#B8905A` | `brand-premium-hover` | `--brand-premium-hover` |
| Text (Warm Brown) | `#2C2417` | `brand-text` | `--brand-text` |
| Background (Cream) | `#FFFCF7` | `brand-bg` | `--brand-bg` |

**Color Notes:**
- Sage is the **primary** brand color — used for buttons, active states, form focus, nav.
- Gold (`#C8A26B`) is the **premium accent** — used ONLY for premium badges, pricing, upsell CTAs. Do NOT use gold as default action color.
- Warm brown (`#2C2417`) is the base text color — used for headings and body.
- Cream (`#FFFCF7`) is the page background — keeps a warm, bridal feel.

### Theme Role Mapping

#### Primary `#92A89C`
- Primary buttons
- Active navigation / sidebar state
- Active tabs, selected cards, radio cards
- Toggle on state
- Form focus ring + border
- Key highlights (non-premium)

#### Primary Hover `#73877C`
- Button hover
- Active pressed state
- Strong borders / selected outlines
- Icon emphasis on hover

#### Primary Soft `#B8C7BF`
- Subtle highlighted surfaces
- Selected rows/cards background
- Empty state accents
- Gentle badges / pills
- Info panels with calm emphasis

#### Premium Gold `#C8A26B`
- Premium badges only
- Pricing highlights
- Upsell CTAs / plan cards
- Locked feature indicators
- Subscription renewal CTAs

#### Text `#2C2417`
- Main body text
- Headings
- Icon default (dark context)
- Table text

#### Background `#FFFCF7`
- App background
- Public page cards
- Large page surfaces

---

### Typography

- **Heading Font:** Playfair Display / Great Vibes (script accents)
- **Body Font:** Figtree (UI), Cormorant Infant (editorial)
- **Mood:** wedding, romance, elegant, editorial, premium
- **Google Fonts:** Playfair Display + Figtree

---

### Spacing Variables

| Token | Value | Usage |
|-------|-------|-------|
| `--space-xs` | `4px` / `0.25rem` | Tight gaps |
| `--space-sm` | `8px` / `0.5rem` | Icon gaps, inline spacing |
| `--space-md` | `16px` / `1rem` | Standard padding |
| `--space-lg` | `24px` / `1.5rem` | Section padding |
| `--space-xl` | `32px` / `2rem` | Large gaps |
| `--space-2xl` | `48px` / `3rem` | Section margins |
| `--space-3xl` | `64px` / `4rem` | Hero padding |

### Shadow Depths

| Level | Value | Usage |
|-------|-------|-------|
| `--shadow-sm` | `0 1px 2px rgba(0,0,0,0.05)` | Subtle lift |
| `--shadow-md` | `0 4px 6px rgba(0,0,0,0.1)` | Cards, buttons |
| `--shadow-lg` | `0 10px 15px rgba(0,0,0,0.1)` | Modals, dropdowns |
| `--shadow-xl` | `0 20px 25px rgba(0,0,0,0.15)` | Hero images, featured cards |

---

## Component Specs

### Buttons

```css
/* Primary Button — Sage */
.btn-primary {
  background: #92A89C;
  color: white;
  padding: 10px 20px;
  border-radius: 10px;
  font-weight: 600;
  transition: all 200ms ease;
  cursor: pointer;
}
.btn-primary:hover {
  background: #73877C;
}

/* Secondary Button */
.btn-secondary {
  background: transparent;
  color: #2C2417;
  border: 1.5px solid #B8C7BF;
  padding: 10px 20px;
  border-radius: 10px;
  font-weight: 600;
  transition: all 200ms ease;
  cursor: pointer;
}
.btn-secondary:hover {
  background: rgba(146,168,156,0.08);
  border-color: #92A89C;
}

/* Premium / Upsell Button — Gold */
.btn-premium {
  background: #C8A26B;
  color: white;
  padding: 10px 20px;
  border-radius: 10px;
  font-weight: 600;
  transition: all 200ms ease;
  cursor: pointer;
}
.btn-premium:hover {
  background: #B8905A;
}
```

### Cards

```css
.card {
  background: #FFFCF7;
  border-radius: 12px;
  padding: 24px;
  box-shadow: var(--shadow-md);
  transition: all 200ms ease;
  cursor: pointer;
  border: 1px solid rgba(184, 199, 191, 0.4);
}
.card:hover {
  box-shadow: var(--shadow-lg);
  transform: translateY(-2px);
}
/* Selected card */
.card.selected {
  border-color: #92A89C;
  background: rgba(146,168,156,0.08);
}
```

### Inputs

```css
.input {
  padding: 12px 16px;
  border: 1px solid #D1D5DB;
  border-radius: 8px;
  font-size: 16px;
  transition: border-color 200ms ease;
}
.input:focus {
  border-color: #92A89C;
  outline: none;
  box-shadow: 0 0 0 3px rgba(146,168,156,0.3);
}
```

### Modals

```css
.modal-overlay {
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
}
.modal {
  background: #FFFCF7;
  border-radius: 16px;
  padding: 32px;
  box-shadow: var(--shadow-xl);
  max-width: 500px;
  width: 90%;
}
```

### Badges

```css
/* General sage badge */
.badge-sage {
  background: rgba(146,168,156,0.15);
  color: #73877C;
  border: 1px solid rgba(146,168,156,0.3);
  border-radius: 9999px;
  padding: 2px 10px;
  font-size: 0.75rem;
  font-weight: 600;
}
/* Premium gold badge — use ONLY for premium contexts */
.badge-premium {
  background: rgba(200,162,107,0.15);
  color: #C8A26B;
  border: 1px solid rgba(200,162,107,0.3);
  border-radius: 9999px;
  padding: 2px 10px;
  font-size: 0.75rem;
  font-weight: 600;
}
```

### Navigation / Sidebar

```css
/* Active nav item */
.nav-item-active {
  background: rgba(146,168,156,0.12);
  color: #2C2417;
}
.nav-item-active .nav-icon {
  color: #73877C;
}
/* Hover nav item */
.nav-item:hover {
  background: rgba(146,168,156,0.06);
}
```

---

## Style Guidelines

**Style:** Editorial Luxury — Calm, Romantic, Premium

**Keywords:** sage green, warm cream, earthy luxury, editorial wedding, intimate, refined, modern without generic SaaS

**Avoid:** neon tones, overly gray enterprise, pastel childish, fully monochrome green screens

### Page Pattern

**Dashboard:** calm scan-friendly, soft backgrounds, sage active states, premium in gold
**Landing:** editorial wedding brand, whitespace, cream background, sage accents, dark brown typography, gold only in pricing

---

## Anti-Patterns (Do NOT Use)

- ❌ Using gold/amber as primary action color (gold = premium only)
- ❌ Vibrant & Block-based colors
- ❌ Blue as brand primary (old system — replaced by sage)
- ❌ Hardcoded `amber-*`, `yellow-*` Tailwind classes for UI states (use brand tokens)
- ❌ Emojis as icons — Use SVG icons (Heroicons, Lucide, Simple Icons)
- ❌ Missing `cursor:pointer` — All clickable elements must have it
- ❌ Layout-shifting hovers — Avoid scale transforms that shift layout
- ❌ Low contrast text — Maintain 4.5:1 minimum contrast ratio
- ❌ Instant state changes — Always use transitions (150-300ms)
- ❌ Invisible focus states — Focus states must be visible for a11y

---

## Pre-Delivery Checklist

Before delivering any UI code, verify:

- [ ] Uses brand color tokens (`brand-primary`, `brand-premium`, etc.) or CSS variables
- [ ] No `amber-*`, `yellow-*`, `orange-*`, `blue-*` classes for brand UI states
- [ ] Gold (`brand-premium` / `#C8A26B`) used ONLY for premium/upsell contexts
- [ ] Sage (`brand-primary` / `#92A89C`) used for all primary actions
- [ ] Background is `brand-bg` / `#FFFCF7` (warm cream)
- [ ] Text is `brand-text` / `#2C2417` (warm brown) for headings and body
- [ ] No emojis used as icons (use SVG instead)
- [ ] All icons from consistent icon set (Heroicons/Lucide)
- [ ] `cursor-pointer` on all clickable elements
- [ ] Hover states with smooth transitions (150-300ms)
- [ ] Light mode: text contrast 4.5:1 minimum
- [ ] Focus states visible for keyboard navigation
- [ ] `prefers-reduced-motion` respected
- [ ] Responsive: 375px, 768px, 1024px, 1440px
- [ ] No content hidden behind fixed navbars
- [ ] No horizontal scroll on mobile
