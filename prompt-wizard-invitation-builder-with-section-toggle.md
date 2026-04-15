# Prompt — TheDay Wizard-Based Invitation Builder With Section Toggles

You are implementing the **wizard-based invitation builder** for TheDay.

Context:
- TheDay is a premium, mobile-first digital wedding invitation SaaS for Indonesia.
- The previous idea of a full section editor is no longer the primary UX direction.
- The product is returning to a **step-by-step wizard flow** because it is more mobile-friendly, easier for non-technical users, and better aligned with how Indonesian couples fill invitation data from phones.
- The wizard should remain simple, focused, and structured.
- However, the product still needs to support many invitation sections, including optional sections that can be turned on/off.
- The implementation must combine a simple wizard UX with scalable section-based data storage.

This means:
- UI = wizard flow
- Data model = structured per section
- Optional sections = toggle on/off within relevant wizard steps

---

## Core Product Decision
Do **not** implement a full editor workspace with persistent section sidebar, multi-panel layout, or heavy preview-focused builder UI.

Instead, implement:
- a **wizard-based builder** with 5-6 main steps
- multiple section cards inside each step
- toggle on/off for optional sections
- accordion/collapsible section cards for mobile comfort
- lightweight preview behavior, not a full editor canvas

This builder must feel easy, guided, and premium.

---

## Main UX Goal
Help users create a complete invitation quickly on mobile without being overwhelmed.

The builder should:
- break the invitation into logical steps
- avoid too many steps
- avoid one endless form
- avoid complex editor mental model
- keep optional sections accessible but not intrusive
- clearly indicate which parts are required and which are optional

---

## Required Wizard Structure
Implement the invitation builder with **6 steps**:

1. `Informasi`
2. `Acara`
3. `Media`
4. `Interaksi`
5. `Tampilan`
6. `Publikasi`

Each step contains one or more section cards.
Do not create one step per section.

---

## Section Mapping Per Step

### Step 1 — Informasi
Contains:
- `cover` (required)
- `konten_utama` (required)
- `couple` (required)
- `quote` (optional)

### Step 2 — Acara
Contains:
- `events` (required)
- `countdown` (optional)
- `location` (required if offline event)
- `live_streaming` (optional)
- `additional_info` (optional)

### Step 3 — Media
Contains:
- `gallery` (optional)
- `video` (optional)
- `love_story` (optional)

### Step 4 — Interaksi
Contains:
- `rsvp` (optional but strongly recommended)
- `wishes` (optional)
- `gift` (optional)

### Step 5 — Tampilan
Contains:
- `music` (optional)
- `theme_settings` (required)
- `section_visibility_summary` (system-generated helper block)

### Step 6 — Publikasi
Contains:
- `slug_settings` (required)
- `password_protection` (optional)
- `preview_and_publish` (required)

---

## Required Section Card Behavior
Each section inside a step must be displayed as a **card**.

Each card header should show:
- section title
- short helper description
- required/optional badge
- completion status badge
- toggle on/off only for optional sections
- expand/collapse icon

### Required sections
Required sections:
- cannot be toggled off
- must always remain visible in the flow
- may still be collapsed when not active

### Optional sections
Optional sections:
- can be toggled on/off
- should remain visible as collapsed cards even when turned off
- when off, card header shows status `Nonaktif`
- when turned on, the fields become editable
- when turned off, public invitation renderer must not display the section

Do not hard-delete section data when the user turns a section off.
Persist the data so it can be restored if re-enabled.

---

## Card UI Rules
Use accordion-style cards for each section.

### Default card behavior
- first incomplete required card in a step should open by default
- completed cards may remain collapsed
- optional disabled cards should remain collapsed
- only one or multiple cards may be expanded depending on implementation simplicity, but mobile usability should be prioritized

### Card header status
Each card should show one of:
- `Wajib`
- `Opsional`
- `Lengkap`
- `Belum lengkap`
- `Nonaktif`
- `Error`

Avoid using too many colors at once. Keep premium and calm styling.

---

## Mobile-First Behavior
This feature must be designed primarily for mobile portrait usage.

### Mobile requirements
- use single-column layout only
- section cards stacked vertically
- large tap targets for toggles, accordions, and action buttons
- sticky bottom action bar with `Kembali` and `Simpan & Lanjut`
- avoid sidebars and multi-column editing layouts
- preview should appear as modal, drawer, or dedicated button action, not permanent side-by-side panel

### Desktop behavior
- still use wizard flow
- can use slightly wider card layout
- may show lightweight preview panel only if it does not reduce clarity
- do not become a full editor workspace on desktop either

---

## Step Navigation Rules
At the top of the wizard, show step progress indicators similar to the current UI.

Each step indicator should show:
- step number
- label
- current state: active, completed, not started

### Navigation behavior
- user can go next and previous
- user may jump back to completed previous steps
- forward jump to later steps may be allowed only if minimum required data has been saved or if the product intentionally allows skipping
- save current step before moving forward

### Save button behavior
Primary CTA:
- `Simpan & Lanjut`

Secondary CTA:
- `Kembali`

Optional tertiary actions:
- `Simpan Draft`
- `Preview`

---

## Step Completion Rules
A step is considered complete when all required sections within that step are valid enough.

Examples:
- `Informasi` complete when `cover`, `konten_utama`, and `couple` are complete
- `Acara` complete when `events` is complete and `location` is complete if the invitation is an offline event
- `Media` can be complete even if all optional sections are disabled
- `Interaksi` can be complete even if all optional sections are disabled
- `Tampilan` complete when minimum theme settings are valid
- `Publikasi` complete when slug and publish requirements are valid

---

## Section Toggle Rules
Implement toggle behavior carefully.

### Toggle ON
When user enables an optional section:
- card becomes active
- fields become editable
- initialize section data using template defaults if empty
- compute completion status normally

### Toggle OFF
When user disables an optional section:
- ask no destructive confirmation unless there is unsaved data loss risk
- keep saved data in persistence
- mark `is_enabled = false`
- collapse the card
- renderer excludes the section from public invitation
- validation should not block publish for disabled section

### Disabled section status
Disabled sections should not count as incomplete.
They should count as intentionally skipped.

---

## Suggested Data Persistence Model
Even though the UI is wizard-based, store data by section.

Each section should have at least:
- `section_key`
- `step_key`
- `is_enabled`
- `is_required`
- `completion_status`
- `data_json`
- `style_json` if needed
- `sort_order` if relevant

Recommended table strategy:
- `invitations`
- `invitation_sections`
- optionally `templates`, `template_sections`, `section_variants` if still useful

Do not store one giant monolithic JSON blob without section separation.

---

## Suggested Step Keys
Use these stable step keys:
- `informasi`
- `acara`
- `media`
- `interaksi`
- `tampilan`
- `publikasi`

Recommended `section_key` mapping:
- `cover`
- `konten_utama`
- `couple`
- `quote`
- `events`
- `countdown`
- `location`
- `live_streaming`
- `additional_info`
- `gallery`
- `video`
- `love_story`
- `rsvp`
- `wishes`
- `gift`
- `music`
- `theme_settings`
- `slug_settings`
- `password_protection`
- `preview_and_publish`

---

## Preview Strategy
Do not create a heavy live editor preview.

Use lightweight preview options:
- `Preview` button per step
- `Preview full invitation` button in step 6
- optional section-level mini preview for media or cover

### Preview behavior
- preview opens in modal, drawer, or new route
- preview reflects current saved state
- preview should be fast
- preview should not compete with form space on mobile

---

## Required Field/Section Principles

### Cover (required)
Controls the opening screen before invitation content.
Should support:
- guest personalization
- button text
- cover image
- opening text
- display name source logic

### Konten Utama (required)
Main hero/intro content after cover opens.
Should support:
- title/headline
- subheadline or opening paragraph
- event summary snippet if needed

### Couple (required)
Primary couple identity section.
Should be the main source of truth for bride/groom data.
Other sections like cover may display derived names but should not become the main identity source.

### Quote (optional)
Can be turned off safely.

### Events (required)
Must support one or more event items.

### Countdown (optional)
Can be enabled only if event date exists.
If no event date exists, show warning or disable toggle.

### Location (conditional required)
If event mode is offline, location required.
If online-only/live-stream-only event, location may be optional.

### Gallery / Video / Love Story
Optional media sections.
Should not block publish if disabled.

### RSVP / Wishes / Gift
Optional interaction sections.
Should be easy to enable without overwhelming user.

### Music (optional)
Toggle on/off.
If enabled but no music chosen yet, mark incomplete.

### Theme Settings (required)
At minimum includes template visual choices needed for rendering.

### Slug Settings (required)
Must validate uniqueness and URL rules.

### Password Protection (optional)
Simple enable/disable with password field.

### Preview and Publish (required)
Show readiness checklist and publish actions.

---

## Example Completion Logic
Section statuses:
- `empty`
- `incomplete`
- `complete`
- `warning`
- `disabled`
- `error`

Step completion should ignore sections with `disabled` status.

Examples:
- `quote` disabled => does not block step completion
- `gallery` enabled but no images => `incomplete`
- `location` missing for offline event => `error` or `incomplete`
- `music` disabled => ignored in step validity

---

## Validation Rules

### General rules
- required sections must be valid before final publish
- disabled optional sections must not run blocking validation
- hidden/collapsed card UI must still preserve validation state visibly in header badge
- unsaved changes must warn before step navigation or page exit

### Step save rules
On clicking `Simpan & Lanjut`:
- validate current step only
- save all sections in current step
- update completion state for the step
- then navigate to next step if valid enough or if skip policy allows it

---

## Suggested UI Components
- `InvitationWizardPage`
- `WizardStepHeader`
- `WizardProgressNav`
- `WizardStickyActionBar`
- `SectionAccordionCard`
- `SectionCardHeader`
- `SectionToggleSwitch`
- `SectionCompletionBadge`
- `SectionFormRenderer`
- `StepPreviewButton`
- `InvitationPublishChecklist`

---

## Edge Cases

### 1. Optional section enabled, user enters data, then disables it
- keep saved data
- mark disabled
- remove from public render
- allow re-enable later with restored data

### 2. Required section accidentally treated as optional by stale client data
- server must enforce `is_required = true`
- reject disabling required sections

### 3. Too many optional sections in one step
- keep all cards collapsed by default except the most relevant one
- do not overwhelm user with all forms open at once

### 4. User opens wizard on mobile and leaves mid-step
- autosave draft when reasonable or save explicitly per step
- restore user to last active step when they return

### 5. Template changes available sections
- wizard should only render sections supported by active template
- unsupported optional sections remain hidden or archived safely

### 6. Step has only optional sections and all are disabled
- step should still count as complete/skippable

### 7. Location disabled for online-only invitation
- do not block publish

### 8. Countdown enabled without event date
- show warning and prevent completion of that section until date exists

### 9. User attempts publish with disabled but data-filled sections
- allowed; disabled sections simply not rendered

### 10. Slug already taken
- show inline validation in step 6
- do not allow publish until resolved

### 11. Password protection enabled but password empty
- mark section incomplete
- block publish until password set or toggle disabled

### 12. Long forms inside card on mobile
- use logical grouping inside the card
- avoid giant uninterrupted form walls

---

## API / Backend Requirements
The backend should support:
- fetching wizard structure for an invitation
- fetching ordered sections grouped by step
- saving all sections inside a step in one request
- toggling section `is_enabled`
- computing section and step completion states
- validating publish readiness

Suggested endpoints:
- `GET /wizard/invitations/{id}`
- `PATCH /wizard/invitations/{id}/steps/{stepKey}`
- `PATCH /wizard/invitations/{id}/sections/{sectionKey}/toggle`
- `GET /wizard/invitations/{id}/preview`
- `POST /wizard/invitations/{id}/publish`

---

## Deliverables
Produce:
1. wizard step configuration
2. section-to-step mapping
3. accordion card UI with toggle support
4. validation and completion logic
5. mobile-first sticky action bar
6. step save + next/previous navigation behavior
7. backend schema/contract for per-section persistence
8. preview strategy implementation
9. graceful handling of all edge cases in this spec

---

## Non-Goals
Do not implement:
- full drag-and-drop editor
- persistent editor sidebar
- desktop-only 3-column builder
- freeform section positioning
- page-builder style interactions

---

## Acceptance Criteria
Implementation is successful when:
1. The invitation builder remains a simple wizard.
2. All needed sections are supported without creating too many steps.
3. Optional sections can be toggled on/off safely.
4. Mobile usage feels comfortable and not overwhelming.
5. Required sections still enforce invitation completeness.
6. Disabled optional sections do not block publish.
7. Data persists per section in a structured way.
8. The overall experience feels premium, guided, and aligned with TheDay’s mobile-first positioning.
