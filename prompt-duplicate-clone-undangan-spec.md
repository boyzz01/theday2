# Prompt — TheDay Duplicate / Clone Invitation Feature Spec

You are implementing the **Duplicate / Clone Invitation** feature for TheDay.

Context:
- TheDay is a premium, mobile-first digital wedding invitation SaaS for Indonesia.
- Users can create invitations with template, invitation content, events, gallery, music, settings, guest list support, and other related data.
- Users may want to duplicate an existing invitation to reuse it as a starting point instead of creating from scratch.
- This is useful for:
  - trying a different version of the same invitation
  - creating a new invitation for another event with similar structure
  - experimenting safely without modifying the original
  - improving retention by making repeat creation easier
- The duplicate feature must feel safe, predictable, and fast.

---

## Core Product Decision
Users must be able to duplicate an existing invitation from their dashboard.

The duplicated invitation becomes a **new draft invitation** owned by the same user.
It starts with copied content and settings, but should not carry over data that would be dangerous or confusing to duplicate blindly.

---

## Where Duplicate Is Accessible

### 1. Invitation list (Undangan Saya)
- Each invitation card or table row should have a secondary action menu (kebab menu / dropdown)
- Include action: `Duplikat Undangan`

### 2. Invitation detail page
- Show action button or dropdown: `Duplikat`

### 3. Optional after publish flow
- On publish success page, optionally show helper CTA:
  - `Duplikat undangan ini`

---

## Expected User Outcome
When user duplicates an invitation:
- a new invitation record is created
- it has the same base content and configuration as the source invitation
- it is saved as **draft**, not published
- it has its own unique ID and slug handling
- original invitation remains unchanged

---

## What Must Be Duplicated
Duplicate these parts from the source invitation:

### Core invitation data
- title / invitation name
- selected template
- section content (cover, couple, events, gallery references, music config, quote, RSVP settings, gift settings, etc.)
- visual settings / theme settings
- section enabled/disabled states
- section order if applicable
- password protection setting value only if safe to duplicate
- schedule-related configuration if it belongs to invitation content

### Media references
- Reuse existing uploaded files if storage model supports shared file references safely
- Or duplicate media association records without re-uploading files
- Do not duplicate binary file storage physically unless required

### Invitation configuration
- template_id
- theme settings
- publish settings except live publication state
- premium feature settings only if the user's current plan still allows them

---

## What Must NOT Be Duplicated
Do **not** duplicate these parts by default:

### Publication / identity
- public slug exactly as-is
- published_at
- live/public URL state
- visitor analytics
- invitation views count
- public share stats

### Guest-related operational data
- guest list
- send status
- RSVP responses
- guest messages / buku tamu entries
- last sent timestamps
- attendance stats

### Payment / subscription operational data
- invoice records
- payment transaction records
- upgrade history

### Audit / lifecycle metadata
- created_at / updated_at from source
- deleted_at
- event logs

The duplicate must start clean from an operational perspective.

---

## Slug Handling Rules
Slug is one of the most important duplication rules.

### Behavior
If source invitation has slug `ardi-novia`, the duplicated invitation must NOT reuse it directly.

Use one of these patterns:
- `ardi-novia-copy`
- `ardi-novia-copy-2`
- `ardi-novia-draft`
- system-generated unique fallback if conflict exists

### Rule
- slug must remain unique across all invitations
- duplicated invitation should start unpublished
- user can edit the slug later before publishing

### If source invitation has no slug yet
- duplicated invitation may also start with null slug or generated draft slug

---

## Draft Status Rules
Every duplicated invitation must start in a safe draft state.

### Required behavior
- `status = draft`
- `published_at = null`
- `is_published = false`
- `publish readiness` should be recalculated

Even if the source invitation is already published, the clone must not auto-publish.

---

## Naming Rules
To make it obvious that this is a copy:

### Duplicated title
If source title is:
- `Undangan Ardi & Novia`

Then default duplicate title may become:
- `Undangan Ardi & Novia (Salinan)`
- or `Undangan Ardi & Novia — Copy`

Use Bahasa Indonesia in UI:
- recommended suffix: `(Salinan)`

### If multiple copies exist
Use increment:
- `(Salinan 2)`
- `(Salinan 3)`

---

## User Flow

### Trigger
User clicks `Duplikat Undangan`

### Confirmation dialog
Show confirmation dialog before duplication:
- Title: `Duplikat undangan ini?`
- Body: `Kami akan membuat salinan baru dari undangan ini sebagai draft. Data tamu, RSVP, ucapan, dan statistik tidak akan ikut disalin.`
- Primary CTA: `Ya, Duplikat`
- Secondary CTA: `Batal`

### On success
Show toast / success feedback:
- `Undangan berhasil diduplikat.`
- Secondary CTA optional: `Buka salinan`

### Redirect behavior
One of these acceptable patterns:
- Stay on the current page and refresh the list with the new draft visible
- Or redirect directly to the duplicated invitation detail/builder page

Recommended:
- on invitation list → stay in list and show success toast with `Buka salinan`
- on detail page → redirect to the new draft detail page

---

## Backend Duplication Logic
Create a dedicated duplication service.

### Suggested service
`DuplicateInvitationService`

### Responsibilities
- validate ownership
- clone invitation core record
- clone section/content records
- clone allowed media associations
- reset forbidden operational data
- generate unique slug and duplicate title
- return new invitation instance

### Transaction
Duplication must run inside a DB transaction.
If any step fails, roll back everything.

---

## Data Model Duplication Map

### `invitations`
Copy:
- template_id
- title/name
- plan-dependent feature flags if still allowed
- theme settings
- content summary fields if any

Reset:
- id (new)
- slug (new unique)
- published_at = null
- status = draft
- analytics counters = 0
- created_at / updated_at = now

### `invitation_sections`
Copy:
- section_key
- step_key
- is_enabled
- is_required
- completion_status may be recalculated or copied then recalculated
- data_json
- style_json if still valid
- sort_order
- is_visible_in_template

Reset / recompute:
- ids
- timestamps
- validation state if derived dynamically

### `invitation_media` or similar relation
Copy associations safely.
Do not re-upload media.

### `guests`
Do not copy.

### `guest_messages`
Do not copy.

### `rsvp_responses`
Do not copy.

### `analytics`
Do not copy.

---

## Plan / Feature Compatibility
If the source invitation uses premium features but the user's current plan no longer allows them:

### Required behavior
- duplication should still succeed if possible
- but unsupported premium features should be downgraded gracefully

Examples:
- custom domain not copied if current plan does not allow it
- premium-only password protection may be disabled if user is on Free
- premium template should only remain if allowed by current plan

### If source uses premium template and user downgraded to Free
Choose one policy and implement consistently:
- Preferred: keep the duplicate as draft but block publish until template is changed or upgraded
- Alternative: switch duplicate to nearest free fallback template with notice

Preferred policy is safer and preserves intent.

---

## UI Placement Recommendations

### Invitation card menu
Actions:
- Edit
- Preview
- Ganti Template
- Duplikat Undangan
- Arsipkan / Hapus

### Invitation detail page
Show `Duplikat` in top action area.

### Mobile
- Use bottom sheet or kebab menu
- Avoid exposing too many inline buttons

---

## Edge Cases

### 1. User duplicates a published invitation
- clone becomes draft
- slug changes
- source remains published and unchanged

### 2. User duplicates a draft invitation
- clone also becomes draft
- works the same way

### 3. User duplicates invitation with premium template while now on Free plan
- preserve draft but block publish until upgrade or template change
- show clear warning badge in duplicate

### 4. Source invitation has guest list with hundreds of guests
- none of that operational data is copied
- duplication should stay fast

### 5. Source invitation has hidden/unsupported sections
- preserve section content and flags in the clone

### 6. Source invitation has broken/missing media references
- clone still succeeds
- invalid references may be skipped with graceful logging

### 7. Duplicate action triggered multiple times quickly
- create separate copies safely
- each gets unique title and slug

### 8. User reaches invitation limit on current plan
- if plan has invitation count limit, duplication should respect it
- show upgrade prompt if creating a copy would exceed plan limit

### 9. Source invitation is soft deleted / archived
- duplication should be blocked unless product intentionally allows restoring from archive

### 10. Source invitation belongs to another user
- must never be duplicable
- return unauthorized

---

## Invitation Limit Handling
TheDay plans include invitation limits [Free: 1 active invitation, Silver: 5 active invitations, Gold: unlimited].

### Required behavior
Before duplication:
- check current user's active invitation count vs plan limit
- if limit exceeded, block duplication
- show upsell dialog:
  - `Batas undangan aktif paketmu sudah tercapai.`
  - `Upgrade untuk membuat undangan baru atau salinan.`

Do not create the duplicate if limit is exceeded.

---

## Suggested API Endpoints
- `POST /dashboard/invitations/{id}/duplicate`
- optional response includes duplicated invitation summary

Response example:
- new invitation id
- new title
- new slug
- status = draft
- redirect URL to edit page

---

## Validation & Authorization
- user must own the source invitation
- invitation must not be deleted permanently
- plan limit must allow creating a new invitation
- duplication service must enforce all rules server-side, not only UI-side

---

## UI Components
- `DuplicateInvitationAction`
- `DuplicateInvitationConfirmDialog`
- `DuplicateInvitationSuccessToast`
- `InvitationLimitUpgradePrompt`

---

## Deliverables
Produce:
1. `POST /dashboard/invitations/{id}/duplicate` endpoint
2. `DuplicateInvitationService` with DB transaction
3. duplication logic for invitation + sections + allowed media associations
4. reset logic for slug, publication state, analytics, guests, RSVP, guest messages
5. invitation limit checking by plan
6. confirmation dialog UI
7. success toast + redirect/open-copy behavior
8. integration in invitation list and invitation detail page
9. edge case handling per spec

---

## Non-Goals
Do not implement:
- duplicating guest list by default
- duplicating analytics or operational history
- cross-user invitation cloning
- template conversion beyond normal duplicate behavior

---

## Acceptance Criteria
Implementation is successful when:
1. User can duplicate an invitation from list and detail page.
2. The duplicate is created as a new draft invitation.
3. Content and design configuration are copied correctly.
4. Guest list, RSVP, guest messages, analytics, and send history are not copied.
5. Slug is regenerated uniquely.
6. Source invitation remains unchanged.
7. Plan limits are respected.
8. The flow feels safe, clear, and useful for experimentation and reuse.
