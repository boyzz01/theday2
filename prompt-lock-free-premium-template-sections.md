# Prompt — Lock Free vs Premium Sections for Invitation Template Wizard

You are refactoring TheDay invitation creation/edit flow for a new **2-tier plan model**:
- **Free**
- **Premium**

Context:
- TheDay is a digital wedding invitation SaaS for Indonesia.
- Free users should only access **free templates** and a **simpler wizard**.
- Premium users get full access to premium templates and advanced sections/features.
- The product must preserve its **value-first flow**: users should still feel they can genuinely create and publish a beautiful invitation on Free.
- Do not make the Free experience frustrating, overly locked, or deceptive.
- The goal is to reduce complexity for Free users while creating clear, natural upgrade moments.

Use the current invitation template structure as source of truth.

---

## Current Template Sections Identified
Based on the current template implementation, the invitation supports these sections/features:

### Core/base sections
- Cover
- Opening / Sambutan
- Couple profile
- Events (date, time, venue, maps)
- Countdown
- Gallery
- RSVP
- Wishes / Ucapan
- Closing

### Optional / advanced sections
- Quote
- Live Streaming
- Additional Info
- Love Story
- Video
- Gift / Amplop Digital
- Music

---

## Product Decision
Implement the following access model.

### Free user gets access to:
- Free templates only
- Cover
- Opening / Sambutan
- Couple profile
- Events
- Countdown
- Gallery
- RSVP
- Wishes / Ucapan
- Closing
- Default music library only
- Max 5 gallery photos
- Watermark remains visible

### Premium user gets access to:
- All free + premium templates
- All core sections
- All optional / advanced sections
- Live Streaming
- Love Story
- Video
- Gift / Amplop Digital
- Additional Info
- Quote
- Upload own music
- Premium template-specific sections if any
- Other premium features such as remove watermark, custom slug, etc.

---

## Important UX Rule
Do **not** simply show the entire full wizard to Free users and block saves at the end.
That creates frustration.

Instead:
- Free users should see a **shorter wizard** focused on core sections.
- Premium-only sections should either be:
  1. hidden entirely from the main Free wizard flow, or
  2. shown as clearly locked upgrade cards outside the main step path.

Preferred approach:
- Keep Free wizard fast and light.
- Show Premium sections in a separate area like:
  - "Tambahkan fitur premium"
  - "Buka fitur lanjutan"
  - "Tersedia di Premium"

---

## Section Classification

### Free sections (editable in Free)
1. Cover
2. Opening / Sambutan
3. Couple profile
4. Events
5. Countdown
6. Gallery
7. RSVP
8. Wishes / Ucapan
9. Closing
10. Music selection from default library only

### Premium-only sections (locked for Free)
1. Live Streaming
2. Love Story
3. Video
4. Gift / Amplop Digital
5. Additional Info
6. Quote
7. Upload own music / custom audio file

---

## Wizard Refactor Rules

### For Free users
Refactor the wizard so it only includes relevant core steps.
Example step grouping:
1. Template
2. Mempelai
3. Acara
4. Galeri
5. RSVP & Ucapan
6. Musik
7. Preview & Publish

Rules:
- Do not include Live Streaming, Love Story, Video, Gift, Quote, or Additional Info as normal wizard steps for Free.
- If those sections currently exist in config/sections, they should not appear as editable steps in Free mode.
- If existing invitation data contains premium sections while user is Free, show them as locked read-only summaries with upgrade CTA, or hide them safely depending on UX consistency.

### For Premium users
Show full wizard or advanced section manager with all available sections.
Premium users can enable/disable all supported sections.

---

## Template Access Rules
- Free users may only choose templates marked as `free`.
- Premium templates must show a clear locked state and Premium badge.
- Free users may preview premium templates, but cannot select/publish them unless they upgrade.
- If a user downgrades from Premium to Free and already uses a premium template, preserve data but mark the invitation as requiring Premium to edit or republish with that template.

---

## Data Model / Config Direction
Implement section gating through centralized configuration, not scattered hardcoded checks.

Recommended structure:
- template has `tier: 'free' | 'premium'`
- section definitions include metadata like:
  - `key`
  - `label`
  - `tier`
  - `visible_in_free_wizard`
  - `editable_by_free`
  - `upgrade_copy`

Example:
```php
return [
  'gallery' => [
    'label' => 'Galeri',
    'tier' => 'free',
    'visible_in_free_wizard' => true,
    'editable_by_free' => true,
  ],
  'livestreaming' => [
    'label' => 'Live Streaming',
    'tier' => 'premium',
    'visible_in_free_wizard' => false,
    'editable_by_free' => false,
    'upgrade_copy' => 'Tambahkan link live streaming dengan Premium.',
  ],
];
```

Do not rely on fragile checks like repeated inline `if ($user->plan === 'free')` across many components.
Use reusable helpers/policies.

---

## UI Behavior for Locked Sections
For Free users, premium sections should appear in one of these patterns:

### Pattern A — hidden from main flow
Use for complexity-heavy items:
- Live Streaming
- Love Story
- Video
- Gift

### Pattern B — visible locked teaser card
Use when upsell value is strong:
- Quote
- Additional Info
- Upload own music

Locked card should show:
- section name
- short value explanation
- Premium badge
- CTA: "Upgrade ke Premium"

Example copy:
- "Tambahkan kisah cinta kalian dalam timeline yang elegan."
- "Pasang link live streaming untuk tamu yang berhalangan hadir."
- "Tampilkan video prewedding atau teaser perjalanan cinta kalian."
- "Aktifkan amplop digital untuk tamu yang ingin mengirim hadiah."

---

## Public Invitation Rendering Rules
Rendering logic must respect section enablement and plan access safely.

Rules:
- Free invitations should not render premium-only sections unless the invitation has valid Premium entitlement.
- If old data contains premium section payloads but the user is now Free, do not break the page.
- Either:
  - hide premium sections in public render, or
  - render only if entitlement is active.

Recommended approach:
- public rendering checks both section enabled state and access entitlement.

---

## Edge Cases
Handle these cases carefully:
1. Existing Free invitations with old advanced section data.
2. Premium user downgrades to Free.
3. Invitation created while Premium, then Premium expires.
4. Free user previews premium template but cannot publish it.
5. Free user opens old draft containing premium sections.
6. Free user tries direct URL access to premium section editor.
7. API requests attempting to save premium section payloads from Free plan.

In all such cases:
- do not lose user data unnecessarily,
- do not allow unauthorized editing/publishing,
- show clear upgrade messaging where appropriate.

---

## Backend Enforcement
Implement real backend authorization, not UI-only locking.

Require enforcement at:
- controller/request validation layer
- service layer / invitation builder layer
- template selection endpoints
- section save/update endpoints
- publish endpoint

Free users must not be able to bypass lock through manual requests.

---

## Copy Style
Use Bahasa Indonesia that feels warm and helpful, not aggressive.

Good examples:
- "Fitur ini tersedia di Premium."
- "Tambahkan live streaming agar tamu yang berhalangan tetap bisa ikut menyaksikan."
- "Upgrade ke Premium untuk membuka template dan fitur lanjutan."

Avoid hostile copy like:
- "Akses ditolak"
- "Anda tidak berhak"
- "Fitur terkunci"

Preferred tone:
- elegant
- helpful
- persuasive
- non-pushy

---

## Deliverables
Produce implementation for:
1. Template tier classification (free vs premium)
2. Centralized section access config
3. Wizard step refactor for Free vs Premium
4. Locked premium teaser cards / upsell blocks
5. Backend policy enforcement for template + section access
6. Publish validation to block premium template/sections for Free users
7. Safe rendering logic for public invitation pages
8. Migration/compatibility handling for old invitation data
9. Reusable helpers such as `isPremium()`, `canUseTemplate()`, `canEditSection()`

---

## Acceptance Criteria
Implementation is successful when:
1. Free users only see and use free templates.
2. Free users get a shorter, simpler wizard focused on core invitation creation.
3. Premium-only sections are not cluttering the Free wizard.
4. Premium sections still create upgrade motivation through tasteful upsell UI.
5. Backend fully enforces access restrictions.
6. Old data remains safe and does not break rendering.
7. TheDay still feels value-first, not overly restrictive.
