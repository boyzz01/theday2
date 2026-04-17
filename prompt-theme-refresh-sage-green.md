# Prompt — TheDay UI Theme Refresh to Sage Green Palette

You are refactoring the visual theme system of TheDay to use a new **sage green primary palette**.

Context:
- TheDay is a premium, mobile-first digital wedding invitation SaaS for Indonesia.
- Current branding uses warm gold as the primary accent, but the product is being refreshed to feel calmer, more modern, more natural, and more editorial.
- The new direction should still feel elegant, warm, premium, and wedding-appropriate.
- Do **not** make the UI feel like a finance app, admin template, productivity app, or healthcare dashboard.
- Preserve TheDay's emotional tone: romantic, calm, premium, approachable.

---

## New Color System
Use the following as the new source of truth for app branding:

- **Primary:** `#92A89C`
- **Primary darker hover:** `#73877C`
- **Primary soft background:** `#B8C7BF`
- **Accent premium:** `#C8A26B`
- **Text:** `#2C2417`
- **Background:** `#FFFCF7`

These colors replace the old "gold-first" feel with a "sage-first" feel.
Gold must still remain in the system, but only as a premium accent — not as the main UI color.

---

## Design Goal
The updated UI should feel:
- calm
- refined
- warm
- premium
- romantic without being overly feminine
- modern without looking generic SaaS

Reference mood:
- editorial wedding brand
- Pinterest-inspired modern wedding aesthetic
- earthy luxury
- intimate celebration

Avoid:
- neon green tones
- overly gray enterprise UI
- overly pastel childish UI
- fully monochrome green screens

---

## Theme Role Mapping

### Primary `#92A89C`
Use for:
- primary buttons
- active navigation state
- tabs active state
- selected cards / radio cards
- toggle on state
- links when appropriate
- key highlights that are not premium-specific

### Primary darker hover `#73877C`
Use for:
- button hover state
- active pressed state
- stronger borders or selected outlines
- icon emphasis on hover

### Primary soft background `#B8C7BF`
Use for:
- subtle highlighted surfaces
- selected rows/cards background
- empty state accents
- gentle badges or pills
- info panels that need calm emphasis

### Accent premium `#C8A26B`
Use only for:
- premium badges
- pricing highlights
- upsell CTAs or plan emphasis
- premium template labels
- locked feature upsell indicators

Do not use gold as the default action color anymore.
Gold should signal premium value, not general interaction.

### Text `#2C2417`
Use for:
- main body text
- headings
- icon default color when dark tone needed
- table text

### Background `#FFFCF7`
Use for:
- app background
- cards on public pages if appropriate
- large page surfaces
- keep warm, airy, bridal feeling

---

## Global Refactor Requirements
Refactor the UI consistently across:
- landing page
- auth pages
- dashboard layout
- invitation list / detail pages
- guest list / RSVP / ucapan pages
- checklist and budget planner pages
- template gallery
- paket & langganan page
- settings page
- legal/contact/public support pages

This should be a system-level theme update, not ad hoc per-page changes.

---

## Tailwind / Design Token Refactor

### If using Tailwind CSS
Update Tailwind theme tokens so colors are semantic, not hardcoded by page.

Recommended semantic tokens:
- `brand-primary`
- `brand-primary-hover`
- `brand-primary-soft`
- `brand-premium`
- `brand-text`
- `brand-bg`

Example mapping:
```js
colors: {
  brand: {
    primary: '#92A89C',
    'primary-hover': '#73877C',
    'primary-soft': '#B8C7BF',
    premium: '#C8A26B',
    text: '#2C2417',
    bg: '#FFFCF7',
  }
}
```

Replace hardcoded old gold/cream colors with semantic tokens wherever possible.

---

## UI Component Rules

### Buttons

#### Primary button
- background: `#92A89C`
- text: white or high-contrast dark depending on accessibility result
- hover: `#73877C`
- border radius: soft, elegant, not too sharp
- shadow: subtle, not aggressive

#### Secondary button
- background: transparent or soft cream
- border: sage or warm neutral
- text: `#2C2417`
- hover: very light sage tint

#### Premium / upsell button
- background: `#C8A26B`
- hover: slightly darker gold derived shade
- use sparingly only for premium conversion moments

### Badges / pills
- normal status badges may use soft sage background
- premium badge must use gold accent
- warning/error colors stay standard accessible palettes, but harmonize with the warm base UI

### Navigation
- active sidebar item background: soft sage tint
- active icon/text: darker sage or text brown
- hover states: subtle sage wash, not gold

### Forms
- focus ring: sage primary
- selected checkbox/radio: sage primary
- input border default: warm neutral gray
- input hover/focus should feel soft and premium, not bright blue browser default

### Cards
- default cards: off-white / cream with subtle border
- selected or emphasized cards: soft sage background or sage border
- premium plan cards: allow gold border/accent but keep sage foundation

### Tables / Lists
- selected row: soft sage background
- hover row: ultra-light warm tint
- keep readability high with dark brown text

---

## Special Rules for Premium Experience
Premium-related surfaces should use a combination of:
- cream background
- gold accent
- dark brown text
- optional sage support

Do **not** make Premium screens fully gold.
The premium feeling should come from restraint and contrast, not saturation.

Examples:
- Premium plan card: cream base, gold badge, sage support accents
- Locked feature prompt: cream panel, gold badge/icon, sage CTA or gold CTA depending priority

---

## Landing Page Direction
Landing page should feel more editorial and wedding-inspired after the update.

### Apply new palette to:
- hero CTA buttons
- feature icons
- template showcase accents
- pricing section
- FAQ accordion highlights
- footer link hover states

### Maintain emotional wedding feel by using:
- lots of whitespace
- cream background
- dark brown elegant typography
- sage accents in decorative UI
- gold only in premium/pricing emphasis

---

## Dashboard Direction
Dashboard should feel:
- soft and calm
- premium but not corporate
- easy to scan

Update:
- sidebar active state
- stat cards
- filters and selects
- page headers
- action buttons
- empty states
- warning banners

Avoid making the dashboard look like a generic project management app.

---

## Public Invitation Builder Direction
In the builder/editor pages:
- use sage for navigation, stepper, form focus, selected state
- use gold only when denoting premium options or upsell paths
- template cards currently selected should feel calm and elegant, not flashy

---

## Accessibility Requirements
- Ensure text contrast is AA-compliant for buttons, pills, badges, and form focus states
- If white text on `#92A89C` is insufficient in some components, use dark text where needed
- Do not rely on color alone to communicate locked/premium/active states
- Preserve focus visibility for keyboard users

---

## Motion & Visual Feel
- Use subtle transitions for hover and selected states (150-250ms)
- Avoid loud animations or flashy color changes
- Visual changes should feel graceful and calm

---

## Scope of Refactor
Produce a complete theme refresh including:
1. Tailwind theme token updates or equivalent design tokens
2. Base button styles
3. Form control styles
4. Sidebar/nav states
5. Badge and pill styles
6. Card states
7. Table row hover/selected states
8. Pricing / premium CTA styling
9. Landing page palette update
10. Dashboard palette update
11. Public utility pages (privacy, terms, contact)

---

## Deliverables
Produce:
1. Updated Tailwind config or theme token source
2. Global CSS variable map if the project uses CSS vars
3. Refactor of shared UI components to use semantic tokens
4. Updated dashboard layout styling
5. Updated landing page section styling
6. Updated buttons, forms, nav, cards, badges, tables
7. Premium accent treatment using gold only where appropriate
8. Audit of hardcoded old color classes and replace them
9. Ensure visual consistency across both dashboard and public pages

---

## Acceptance Criteria
Implementation is successful when:
1. TheDay visually shifts from gold-first to sage-first.
2. Gold is still present, but only as premium accent.
3. Dashboard feels calm, premium, and modern.
4. Landing page still feels romantic and wedding-appropriate.
5. Forms, buttons, nav, and cards all use the new semantic theme consistently.
6. The product no longer feels like a generic admin dashboard.
7. Accessibility and contrast remain acceptable.
8. The overall brand feels more refined, fresh, and emotionally warm.
