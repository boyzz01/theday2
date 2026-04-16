# Prompt — TheDay Change Template Feature Spec

You are implementing the **ganti template** (change template) feature for TheDay.

Context:
- TheDay is a premium, mobile-first digital wedding invitation SaaS for Indonesia.
- Users create invitations using a wizard-based builder with step-by-step flow.
- Currently, once a user selects a template when creating an invitation, they cannot change it afterward.
- This must be changed: users should be able to change their invitation template at any time during editing.
- Invitation data (couple names, event details, gallery, etc.) must be preserved when changing templates.
- This is a significant UX improvement that reduces commitment anxiety when choosing a template.

---

## Core Product Decision
Allow users to change the template of an existing invitation at any time.

This means:
- Template selection is not a permanent one-time decision
- Users can explore different templates without losing their data
- The new template renders the same data in its own design style
- Data compatibility between templates must be handled gracefully

---

## Where "Ganti Template" is Accessible
The change template action must be accessible from multiple entry points:

### 1. Inside the wizard builder (Step Tampilan)
- In the Tampilan step, show current active template
- Add button: "Ganti Template"
- Opens template picker/gallery

### 2. From invitation detail / dashboard
- In the invitation detail page or card
- Add action: "Ganti Template"
- Accessible without entering the full wizard

### 3. From invitation list (Undangan Saya)
- Each invitation card may have a kebab menu or secondary action
- Include "Ganti Template" option

---

## Template Picker / Gallery UI

### Trigger
When user clicks "Ganti Template", open a full-screen modal or dedicated page showing all available templates.

### Layout
- Grid of template thumbnails
- Each template card shows:
  - Template preview image
  - Template name
  - Category or style tag (e.g. Minimalis, Jawa, Modern, Elegan)
  - Free or Premium badge
  - "Pilih" button
  - Currently active template shows "Aktif" badge

### Filter and search
- Filter by style/category
- Filter by Free / Premium
- Search by template name

### Preview before confirm
Before applying a new template, show a preview:
- "Preview" button per template card
- Opens preview in modal or new tab
- Preview renders current invitation data in the selected template
- If preview is not feasible server-side, show static template demo

### Confirm change
After user selects a template:
- Show confirmation dialog:
  - Title: "Ganti ke template [nama template]?"
  - Body: "Data undanganmu seperti nama, tanggal, dan foto akan tetap tersimpan. Tampilan akan berubah menggunakan template baru."
  - CTA primary: "Ya, Ganti Template"
  - CTA secondary: "Batal"
- On confirm: apply new template and return to builder or invitation detail

---

## Data Preservation Rules
When user changes template, all existing invitation data must be preserved.

### Data that must be preserved
- Couple names
- Event details (date, time, venue, location)
- Gallery photos
- Music selection
- Opening message / kata pembuka
- Closing message / kata penutup
- RSVP settings
- Guest wishes settings
- Gift info
- All other section data

### Data that will change
- Visual design (colors, fonts, ornaments, layout)
- Template-specific style settings that don't transfer

### Section compatibility
Different templates may support different sections.
Handle incompatibility gracefully:

- If new template supports a section that old template did not:
  - Section is available but empty/disabled by default
  - User can fill it in

- If new template does NOT support a section that old template had:
  - Do not delete the section data
  - Store it as inactive/hidden
  - If user switches back to a template that supports it, restore the data

- If new template has different style variants for a section:
  - Apply default style of new template for that section
  - Preserve content data

---

## Template Compatibility Model
Each template defines which sections it supports.

Template config should include:
- `supported_sections[]` — list of section keys the template renders
- `default_section_styles{}` — default style per section for this template
- `required_sections[]` — sections that must be filled for this template

When changing template:
- compute intersection of old and new supported sections
- sections in both: carry over content + apply new template default style
- sections only in old template: keep data, mark as inactive for new template
- sections only in new template: initialize as empty/disabled

---

## Premium Template Gating
If user tries to switch to a premium template on a Free plan:

- Show upgrade prompt instead of applying the template
- Message: "Template ini tersedia untuk paket Silver dan Gold."
- CTA: "Upgrade Sekarang" and "Lihat Template Gratis"
- Do not apply the template until user upgrades

If user is on Silver or Gold:
- All templates in their plan tier are available
- No restriction

---

## Effect on Published Invitations
If the invitation is already published and user changes template:

- Show additional warning in confirmation dialog:
  - "Undanganmu sudah dipublikasi. Mengganti template akan mengubah tampilan yang dilihat tamu segera setelah disimpan."
- On confirm: apply change immediately
- Public invitation URL stays the same
- New template renders immediately for new visitors

---

## Wizard Step Integration
In the Tampilan step of the wizard:

- Show current template as a highlighted card
- Add "Ganti Template" button below or next to current template card
- Current template name and preview image should be visible
- Changing template from here should re-render the Tampilan step with new template context

---

## Invitation List Integration
In Undangan Saya:

- Each invitation card has a context menu or secondary actions
- Add "Ganti Template" to the menu
- Clicking opens template picker modal
- After change, invitation card updates to show new template thumbnail

---

## API / Backend Requirements

### Current flow (read-only reference)
- `invitation.template_id` is set once during creation and never changes
- This must change to allow updates

### Required changes

#### Allow template update
- `PATCH /dashboard/invitations/{id}/template`
- Body: `{ template_id: uuid }`
- Validates:
  - Template exists
  - Template is accessible for user's plan
  - Invitation belongs to user
- On success:
  - Update `invitation.template_id`
  - Run section compatibility logic
  - Return updated invitation data

#### Section compatibility handler
When template changes:
- For each section in invitation:
  - If new template supports it: keep enabled status, update style to new template defaults
  - If new template does not support it: set `is_visible_in_template = false` (do not delete)
- For each section new template supports but invitation doesn't have yet:
  - Create section record with `is_enabled = false` and empty data

#### Template preview with current data
Optional but ideal:
- `GET /dashboard/invitations/{id}/preview?template_id={uuid}`
- Returns preview of how current invitation data looks in the selected template

---

## Data Model Changes

### `invitations` table
- `template_id` UUID — already exists, must now be updatable

### `invitation_sections` table
Add column if not exists:
- `is_visible_in_template` boolean default true
  - false = section data exists but current template does not render it

---

## Validation Rules

### Change template request
- `template_id` required, must exist in `templates` table
- Template must be accessible for user's current subscription plan
- Invitation must belong to authenticated user

---

## Edge Cases

### 1. User changes template with a lot of data already filled
- All data preserved
- Only visual changes
- No data loss in any case

### 2. User changes template back to the original
- Restore all section visibility states as before
- Data still intact

### 3. User changes template multiple times rapidly
- Each change is independent
- Final state is the last selected template
- No cascade errors

### 4. New template does not support gallery section but user has photos
- Photos remain stored in S3
- Gallery section marked as not visible in this template
- If user switches to a template that supports gallery, photos reappear

### 5. User on Free plan tries to select premium template
- Show upgrade prompt
- Do not apply template
- Do not show error, only upsell

### 6. Template is deprecated or removed by admin
- User can still view their invitation with the old template for now
- But template picker should not show deprecated templates
- Optionally prompt user to switch to a new template

### 7. Invitation is currently being viewed by a guest while template changes
- The change is applied immediately on save
- Guest reloads and sees new template
- This is acceptable and expected behavior

### 8. Preview fails to load
- Show static template demo image instead
- Do not block the change flow

### 9. Template change during active event countdown
- Data preserved including event date
- Countdown continues in new template style

### 10. User has no internet during template change
- Show error: "Gagal mengganti template. Periksa koneksi internetmu."
- Do not apply partial changes

---

## UI Components

- `ChangeTemplateButton` — trigger button shown in wizard and invitation detail
- `TemplatePicker` — full-screen modal or page with grid of templates
- `TemplatePickerCard` — individual template card with preview, name, badge, CTA
- `TemplatePickerFilters` — filter bar for style and plan tier
- `TemplatePreviewModal` — preview of template with current invitation data
- `TemplateChangeConfirmDialog` — confirmation before applying change
- `TemplatePremiumUpgradePrompt` — upsell when selecting premium template on Free plan

---

## Deliverables
Produce:
1. `PATCH /dashboard/invitations/{id}/template` endpoint
2. Section compatibility logic when template changes
3. `TemplatePicker` Vue component with grid, filter, search
4. `TemplatePickerCard` component
5. `TemplateChangeConfirmDialog` component
6. `TemplatePremiumUpgradePrompt` component
7. Integration in wizard Tampilan step
8. Integration in Undangan Saya invitation card menu
9. Data model column `is_visible_in_template` if needed
10. Edge case handling per spec

---

## Non-Goals
Do not implement:
- Side-by-side live preview editor when changing template
- Auto-migrate section style settings between templates in complex ways
- Template versioning system

---

## Acceptance Criteria
Implementation is successful when:
1. User can change template from wizard Tampilan step.
2. User can change template from invitation detail or list page.
3. All invitation data is preserved after template change.
4. Section compatibility is handled gracefully without data loss.
5. Premium templates show upgrade prompt for Free plan users.
6. Published invitation updates immediately after template change.
7. Confirmation dialog is shown before applying the change.
8. The feature feels smooth and confidence-inspiring — not scary.
