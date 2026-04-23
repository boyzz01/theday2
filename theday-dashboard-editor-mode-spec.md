# TheDay — Dashboard Sidebar vs Editor Mode Spec

## Objective
Implement a clear layout separation between the main application dashboard and the invitation editor so users can manage their account and invitations without UI confusion, while still getting a focused section-based editing experience for invitation customization.

This spec is intended for AI agents and developers implementing the UX, routing, layout logic, navigation structure, responsive behavior, and edge cases for TheDay.

## Core Product Decision
TheDay must keep the main **dashboard sidebar** for app-level navigation, but the **invitation editor** must use a separate focused editing layout.

This means:
- The main dashboard sidebar still exists.
- The invitation editing page should not behave like a normal dashboard content page.
- When users enter invitation editing, the interface should switch into a dedicated **Editor Mode**.
- In Editor Mode, the primary navigation should be based on invitation sections, not on general dashboard menus.

## UX Principle
Separate navigation by intent:
- **Dashboard navigation** = managing the app, invitations, guests, billing, settings.
- **Editor navigation** = editing the content and appearance of a single invitation.

Users should never have to mentally mix app management with invitation section editing.

## Required Layout Modes
Implement two main layout modes:

### 1. Dashboard Mode
Used for:
- home / beranda
- invitation list
- guest management
- RSVP/message management
- transactions / billing
- add-ons
- account / settings
- template browsing outside active editing flow

Characteristics:
- full application sidebar visible
- standard dashboard header / breadcrumbs
- content pages use dashboard container width
- suitable for tables, cards, analytics, and lists

### 2. Editor Mode
Used for:
- editing a specific invitation
- editing template content
- editing section data
- changing section variants
- configuring theme/style for the active invitation
- previewing the invitation while editing

Characteristics:
- should switch to focused layout
- should minimize dashboard chrome
- should prioritize section list + form panel + live preview
- should feel like a creator/editor workspace, not an admin page

## Layout Requirement
### Dashboard Mode Layout
Recommended structure:
- left: dashboard sidebar
- top: page header / breadcrumb / actions
- main: page content

### Editor Mode Layout
Recommended desktop structure:
- left: editor sidebar with section navigation
- center: section configuration panel / form panel
- right: live preview panel

Optional top bar in Editor Mode may include:
- back to dashboard
- invitation title
- save status
- preview mode toggle (mobile / desktop)
- publish button
- template/style actions

Do not keep the large normal dashboard sidebar fully visible inside Editor Mode.
If needed, show only a compact app switcher or a minimal collapsed rail.

## Navigation Rules
### Dashboard Sidebar Responsibilities
The dashboard sidebar should contain only app-level destinations such as:
- Beranda
- Undangan
- Tamu
- RSVP / Ucapan
- Transaksi
- Add-on
- Pengaturan

It must not contain detailed invitation sections like:
- Cover
- Couple Profile
- Event Details
- Gallery
- RSVP form
- Closing

### Editor Sidebar Responsibilities
The editor sidebar should contain only editable invitation sections for the active invitation.
Example:
- Cover
- Pembuka
- Mempelai
- Kutipan
- Acara
- Countdown
- Lokasi
- Love Story
- Galeri
- Video
- RSVP
- Ucapan
- Hadiah
- Live Streaming
- Info Tambahan
- Penutup

Each section item should support visible state indicators such as:
- active
- completed / has data
- missing required data
- disabled
- optional

## Section Editing Rules
- Clicking a section in Editor Mode opens that section’s form/config panel.
- The live preview should update for the active section.
- Users should be able to enable/disable optional sections.
- Users should be able to reorder supported sections where allowed.
- Users should be able to switch section variants if the current template supports them.
- Required sections should not be removable, only editable.

## Template and Section Relationship
The editor must remain aligned with TheDay’s structured template engine.
This means:
- templates define allowed section variants
- templates may define default section order
- templates may define mandatory sections
- switching templates may affect available section variants
- unsupported section variants must fallback safely

## Route and Layout Behavior
### Example route strategy
Use separate route groups or layout wrappers.

Dashboard Mode examples:
- `/dashboard`
- `/dashboard/invitations`
- `/dashboard/invitations/{id}`
- `/dashboard/guests`
- `/dashboard/billing`
- `/dashboard/settings`

Editor Mode examples:
- `/editor/invitations/{id}`
- `/editor/invitations/{id}/section/{sectionKey}` optional deep-link support
- `/editor/invitations/{id}/theme`
- `/editor/invitations/{id}/preview`

The exact route names can differ, but the layout separation must remain explicit.

## Deep Linking
Editor Mode should support deep linking when practical.
Examples:
- open invitation editor directly on `cover`
- open invitation editor directly on `gallery`
- open invitation editor directly on `rsvp`

Benefits:
- easier support workflows
- easier onboarding guidance
- easier AI-assisted navigation

Fallback behavior:
- if section key is invalid, redirect to default first editable section
- if section is disabled but addressable, open section config and highlight disabled state

## Save Behavior
Editor Mode should have explicit save behavior.
Support one of these patterns consistently:
- autosave with visible saving indicator
- manual save with dirty state detection
- hybrid autosave + explicit publish

Minimum requirements:
- clearly show save state: `Saved`, `Saving...`, `Unsaved changes`, `Failed to save`
- prevent silent data loss
- warn before leaving with unsaved changes

## Responsive Behavior
### Desktop
Use the full Editor Mode layout:
- section sidebar
- form panel
- preview panel

### Tablet
Allow one panel to collapse as needed.
Recommended:
- section sidebar collapsible
- preview as toggled panel or tab

### Mobile
Do not try to keep 3 columns.
Recommended mobile editor behavior:
- top tabs or segmented controls for `Section`, `Edit`, `Preview`
- bottom sheet or drawer for section list
- sticky save / preview / publish actions
- preview should default to mobile frame

The dashboard sidebar on mobile should become a drawer in Dashboard Mode.
Editor Mode should not inherit the full dashboard drawer as the primary editing navigation.

## Transition Between Modes
When user clicks `Edit Invitation` from dashboard:
- leave Dashboard Mode
- enter Editor Mode
- keep a clear `Back to Dashboard` affordance

When user exits Editor Mode:
- return to last relevant dashboard page when possible
- preserve context if practical, such as selected invitation

## Preview Behavior
Preview is critical in Editor Mode.
Requirements:
- preview updates based on active section changes
- support mobile and desktop preview toggles
- scroll preview to related section when editing a specific section
- highlight the active section in preview when practical
- if section is disabled, preview should show the disabled result immediately

## Status Indicators
Each section in the editor sidebar should optionally show:
- required / optional
- completed / incomplete
- hidden / enabled
- validation error
- draft content exists

Example logic:
- `Cover` missing main names = incomplete
- `Acara` missing date/time = warning
- `Galeri` disabled = hidden state
- `RSVP` enabled but missing settings = warning

## Validation Rules
Validation should happen at both field level and section summary level.
Examples:
- Cover requires at least a title or bride/groom name configuration
- Event section requires at least one event item if enabled
- Map section requires either address text or maps URL if enabled
- Gallery section may be enabled with zero images only if template permits placeholder behavior
- RSVP section requires status and form configuration if enabled
- Gift section requires at least one gift method if enabled

The editor sidebar should reflect validation summaries without forcing the user into modal interruptions.

## Edge Cases
### 1. User opens editor for invitation that does not exist
- return 404 or friendly not found page
- do not render broken editor shell

### 2. User opens invitation they do not own
- return 403 or redirect safely
- never leak invitation data

### 3. Invitation draft exists but template config is corrupted
- load safe fallback template
- show warning banner
- preserve user data

### 4. Section key in URL is invalid
- redirect to first valid editable section
- do not crash the editor

### 5. Section is not supported by current template
- show fallback section behavior or hide inaccessible link
- if linked directly, redirect with notice

### 6. User switches template and some section variants become incompatible
- fallback to template default variant
- preserve content data where possible
- surface a non-blocking notice listing affected sections

### 7. Required section is manually disabled via stale data or API inconsistency
- re-enable automatically or block save until fixed
- do not allow published invitation with broken required structure

### 8. Unsaved changes then browser refresh
- restore draft from autosave if available
- otherwise show warning before refresh if possible

### 9. Network failure while saving
- keep local dirty state
- show retry option
- do not falsely show saved state

### 10. User opens editor on mobile with very large forms
- split forms into grouped subsections
- avoid long overwhelming single screens

### 11. Preview rendering error for a section
- isolate preview failure to section component if possible
- show fallback preview block instead of blank screen
- form editing should remain available

### 12. Invitation has optional sections never configured
- keep them available in sidebar under optional sections
- allow one-click enable and configure flow

### 13. User starts editing from onboarding-created draft with minimal data
- required sections should show incomplete state
- editor should guide the user naturally toward completion

### 14. Concurrent editing in multiple tabs
- detect stale version if practical
- warn user that another tab may overwrite changes

### 15. Guest data / RSVP dependent sections before publish
- allow editor UI to show empty state placeholders
- do not treat missing guest responses as validation errors

## Access Control
- Only authenticated users with permission to edit the invitation may enter Editor Mode.
- Dashboard pages and editor pages may share auth middleware, but editor-specific authorization should still be enforced.
- If onboarding completion is required before dashboard/editor access, apply that rule consistently.

## Component Suggestions
Suggested component structure:
- `DashboardLayout`
- `EditorLayout`
- `DashboardSidebar`
- `EditorSidebar`
- `EditorTopbar`
- `SectionFormRenderer`
- `LiveInvitationPreview`
- `SectionStatusBadge`
- `SaveStatusIndicator`

## State Management Requirements
Track these editor states explicitly:
- active invitation
- active section key
- active template
- section enabled/disabled states
- section completion states
- current save status
- current preview mode
- dirty state
- validation errors
- fallback notices after template switch

## Suggested Editor Sidebar Grouping
If the section list becomes long, group sections:

### Wajib
- Cover
- Mempelai
- Acara
- Lokasi
- Penutup

### Cerita & Visual
- Pembuka
- Kutipan
- Love Story
- Galeri
- Video

### Interaksi
- RSVP
- Ucapan
- Live Streaming

### Tambahan
- Hadiah
- Info Tambahan

Grouping improves scanability without changing the section-based editing model.

## Publish Readiness Check
Before publish, evaluate readiness at invitation level:
- required sections complete
- valid public slug exists
- event date rules satisfied if invitation type requires date
- at least one event location/address exists if event is offline
- no blocking validation errors

Do not force all optional sections to be complete.

## Non-Goals
Do not implement:
- freeform drag-anywhere visual builder
- mixing dashboard menus with section menus in one sidebar
- overly complex multi-sidebar nesting that confuses users
- desktop-only editing assumptions

## Acceptance Criteria
The feature is considered successful when:
1. Dashboard sidebar remains available for app-level pages.
2. Invitation editing uses a separate focused Editor Mode.
3. Section-based navigation replaces dashboard navigation inside the editor.
4. Users can edit invitation content section by section without confusion.
5. Desktop, tablet, and mobile behavior remain usable.
6. Invalid routes, stale data, incompatible variants, and save failures are handled gracefully.
7. The UI remains aligned with TheDay’s premium, mobile-first, template-driven experience.

## Implementation Output Required
When implementing this feature, provide:
1. route structure and layout separation
2. dashboard sidebar spec
3. editor sidebar spec
4. responsive behavior notes
5. save-state handling
6. validation and completion logic per section
7. fallback behavior for route/template/section edge cases
8. component map for Laravel + Inertia + Vue implementation
