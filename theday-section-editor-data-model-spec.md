# TheDay — Section-Based Editor Data Model Spec

## Purpose
This document defines the database, model, migration, serialization, and data integrity spec required to support TheDay’s section-based invitation editor.

This spec is written for AI agents and developers implementing the backend and frontend contract for Laravel 11 + MySQL 8 + Inertia/Vue.

This document is intentionally implementation-oriented and should be treated as the source of truth for schema design related to invitation sections.

---

## Product Context
TheDay is moving toward a dedicated invitation editor where users edit invitation content by section, not by one long monolithic form.

Examples of editable sections:
- cover
- opening
- couple
- quote
- events
- countdown
- location
- love_story
- gallery
- video
- rsvp
- wishes
- gift
- live_streaming
- additional_info
- closing

This requires a data structure that:
- supports template-defined section defaults
- supports per-invitation overrides
- supports optional sections
- supports ordering
- supports section variants
- supports section-level validation states
- supports future template scalability

---

## Design Goals
The schema must support:
1. one invitation having many sections
2. template-driven default section definitions
3. section order overrides per invitation
4. optional enable/disable state per section
5. variant selection per section
6. structured but flexible content storage
7. safe fallback when templates evolve
8. future compatibility for new templates and section types
9. minimal schema churn when adding new content fields

The schema should avoid:
- hardcoding every section field as a column in `invitations`
- requiring a migration for every minor content field change
- mixing template metadata directly into live invitation records without traceability

---

## Recommended Architecture
Use a hybrid data model:
- `invitations` stores invitation-level core metadata
- `templates` stores template metadata
- `template_sections` stores section defaults per template
- `section_variants` stores variant metadata
- `invitation_sections` stores per-invitation section state and content overrides
- repeating child content may use JSON by default, unless there is a strong product reason to normalize

This architecture is best for TheDay because it balances:
- flexibility
- implementation speed
- template scalability
- editor simplicity
- lower migration frequency

---

## Main Tables

### 1. `templates`
Purpose:
Stores base invitation template definitions.

Suggested columns:
- `id` UUID primary key
- `code` string unique, machine-readable identifier, example: `sekar_arum`
- `name` string
- `category` string nullable, example: `javanese`, `minimalist`, `modern`
- `status` enum: `draft`, `active`, `archived`
- `thumbnail_url` string nullable
- `preview_url` string nullable
- `supports_reordering` boolean default false
- `supports_variant_switching` boolean default true
- `version` integer default 1
- `meta_json` json nullable
- `published_at` timestamp nullable
- `created_at`
- `updated_at`

Indexes:
- unique index on `code`
- index on `status`

Notes:
- `version` helps future template migrations.
- `meta_json` can store editor hints, style config, supported devices, preview defaults, etc.

---

### 2. `section_variants`
Purpose:
Stores globally reusable section variant definitions.

Example variants:
- `cover.classic_centered`
- `cover.fullscreen_photo`
- `events.timeline`
- `gallery.grid`
- `gallery.carousel`

Suggested columns:
- `id` UUID primary key
- `section_type` string
- `code` string unique
- `name` string
- `status` enum: `active`, `deprecated`, `archived`
- `schema_json` json nullable
- `ui_meta_json` json nullable
- `render_component` string nullable
- `editor_component` string nullable
- `version` integer default 1
- `created_at`
- `updated_at`

Indexes:
- unique index on `code`
- index on `section_type`
- index on `status`

Notes:
- `schema_json` may define field-level expected shape for validation or editor generation.
- `deprecated` variants can still render old invitations safely.

---

### 3. `template_sections`
Purpose:
Defines the default section structure of a template.

This is the template blueprint, not the live invitation content.

Suggested columns:
- `id` UUID primary key
- `template_id` UUID foreign key to `templates.id`
- `section_key` string
- `section_type` string
- `label` string
- `default_variant_id` UUID nullable foreign key to `section_variants.id`
- `is_required` boolean default false
- `is_enabled_by_default` boolean default true
- `is_removable` boolean default true
- `sort_order` unsigned integer
- `supports_multiple_items` boolean default false
- `supports_reordering` boolean default false
- `default_data_json` json nullable
- `default_style_json` json nullable
- `rules_json` json nullable
- `visibility_conditions_json` json nullable
- `created_at`
- `updated_at`

Constraints:
- unique composite index on `template_id + section_key`
- foreign key `template_id`
- foreign key `default_variant_id`

Notes:
- `section_key` should be stable inside a template blueprint.
- `section_type` is semantic, example: `gallery`, `events`, `rsvp`.
- `rules_json` may include editor rules like `min_items`, `max_items`, `requires_date`, etc.

---

### 4. `invitations`
Purpose:
Stores invitation-level core data that should not be scattered into section rows.

Suggested columns:
- `id` UUID primary key
- `user_id` UUID foreign key
- `template_id` UUID foreign key
- `title` string nullable
- `slug` string unique nullable
- `status` enum: `draft`, `published`, `archived`
- `publication_status` enum: `not_ready`, `ready`, `published`, `unpublished` default `not_ready`
- `event_type` string default `wedding`
- `timezone` string default `Asia/Jakarta`
- `locale` string default `id`
- `cover_image_url` string nullable
- `music_asset_id` UUID nullable
- `domain_type` enum: `platform`, `custom` default `platform`
- `custom_domain` string nullable
- `published_at` timestamp nullable
- `last_edited_at` timestamp nullable
- `editor_version` integer default 1
- `meta_json` json nullable
- `created_at`
- `updated_at`

Indexes:
- unique index on `slug`
- index on `user_id`
- index on `template_id`
- index on `status`
- index on `publication_status`

Notes:
- Keep invitation-level concerns here, not per-section content.
- Avoid storing full section payload in `invitations.meta_json` unless it is only cache or derived output.

---

### 5. `invitation_sections`
Purpose:
Stores the live section state for one invitation.
This is the most important new table.

Suggested columns:
- `id` UUID primary key
- `invitation_id` UUID foreign key to `invitations.id`
- `template_section_id` UUID nullable foreign key to `template_sections.id`
- `section_key` string
- `section_type` string
- `label` string nullable
- `variant_id` UUID nullable foreign key to `section_variants.id`
- `is_enabled` boolean default true
- `is_required` boolean default false
- `is_hidden` boolean default false
- `sort_order` unsigned integer
- `completion_status` enum: `empty`, `incomplete`, `complete`, `warning`, `error` default `empty`
- `validation_errors_json` json nullable
- `data_json` json nullable
- `style_json` json nullable
- `meta_json` json nullable
- `last_validated_at` timestamp nullable
- `created_at`
- `updated_at`

Constraints:
- unique composite index on `invitation_id + section_key`
- foreign key `invitation_id`
- foreign key `template_section_id`
- foreign key `variant_id`

Indexes:
- index on `invitation_id`
- index on `section_type`
- index on `is_enabled`
- index on `completion_status`
- index on `sort_order`

Notes:
- `section_key` must be stable within a specific invitation.
- `data_json` stores section content payload.
- `style_json` stores style overrides specific to that section.
- `validation_errors_json` should contain machine-readable errors, not just strings.

---

## Optional Supporting Tables
These are optional and only needed if product scope justifies normalization.

### 6. `invitation_assets`
Use if file/media references need stronger ownership and lifecycle management.

Suggested columns:
- `id` UUID primary key
- `invitation_id` UUID foreign key
- `user_id` UUID foreign key
- `type` enum: `image`, `audio`, `video`, `document`
- `provider` string nullable
- `path` string
- `url` string nullable
- `mime_type` string nullable
- `size_bytes` bigint nullable
- `width` integer nullable
- `height` integer nullable
- `duration_seconds` integer nullable
- `meta_json` json nullable
- `created_at`
- `updated_at`

Use cases:
- gallery images
- cover images
- uploaded music
- video thumbnails

### 7. `invitation_editor_drafts`
Use if you want autosave snapshots or crash recovery beyond the main saved state.

Suggested columns:
- `id` UUID primary key
- `invitation_id` UUID foreign key
- `user_id` UUID foreign key
- `source` enum: `autosave`, `manual_snapshot`, `recovery`
- `payload_json` json
- `version` integer default 1
- `created_at`
- `updated_at`

Use cases:
- restore after browser crash
- restore after broken section schema rollout
- compare last saved editor state

### 8. `template_section_variant_rules`
Use if template-specific variant allowance becomes complex.

Suggested columns:
- `id` UUID primary key
- `template_section_id` UUID foreign key
- `variant_id` UUID foreign key
- `is_default` boolean default false
- `is_enabled` boolean default true
- `rules_json` json nullable
- `created_at`
- `updated_at`

Only add this if a template can allow multiple variants per section with custom constraints.
Otherwise, keep variant allowance in `template_sections.rules_json` for simplicity.

---

## Minimum Required New Tables
If the goal is fast but scalable implementation, the minimum recommended additions are:
1. `template_sections`
2. `section_variants`
3. `invitation_sections`

Optional later:
4. `invitation_assets`
5. `invitation_editor_drafts`
6. `template_section_variant_rules`

This means yes: the new architecture should include new tables.
At minimum, `invitation_sections` is required for a proper section-based editor.

---

## Recommended Relationships
Laravel Eloquent relationship map:

### Template
- `hasMany(TemplateSection)`
- may `hasMany(Invitation)`

### TemplateSection
- `belongsTo(Template)`
- `belongsTo(SectionVariant, 'default_variant_id')`
- optional `hasMany(TemplateSectionVariantRule)`

### Invitation
- `belongsTo(User)`
- `belongsTo(Template)`
- `hasMany(InvitationSection)`
- optional `hasMany(InvitationAsset)`
- optional `hasMany(InvitationEditorDraft)`

### InvitationSection
- `belongsTo(Invitation)`
- `belongsTo(TemplateSection)` nullable
- `belongsTo(SectionVariant, 'variant_id')` nullable

### SectionVariant
- may `hasMany(TemplateSection)` through default usage
- may `hasMany(InvitationSection)`

---

## Why `invitation_sections` Is Necessary
Without `invitation_sections`, you would need one of these poor alternatives:
- put all section content inside a giant JSON on `invitations`
- keep adding columns to `invitations`
- hardcode editor behavior per template without stable persistence

Those approaches become painful when:
- templates evolve
- users reorder sections
- new optional sections are introduced
- variant switching is needed
- editor completion states are needed

`invitation_sections` gives stable per-section persistence and editor-friendly state management.

---

## Section Key Rules
Each section row must have a stable `section_key`.

Recommended convention:
- single instance sections use semantic key, example: `cover`, `couple`, `closing`
- repeated-type sections that can have multiple blocks use suffix, example: `event_main`, `event_akad`, `event_resepsi` only if the product intentionally supports multi-instance section rows

If the product only allows one `events` section containing multiple event items, use:
- `section_key = events`
- event entries stored inside `data_json.items`

Recommended for current scope:
- keep one row per section category
- store repeated child items inside `data_json`
- do not create separate table per section item unless reporting or querying truly requires it

---

## Recommended `data_json` Shapes
Below are suggested payload shapes for section content.

### cover
```json
{
  "headline": "The Wedding Of",
  "subheadline": "We invite you to celebrate our special day",
  "groom_name": "Ahmad",
  "bride_name": "Siti",
  "cover_image": {
    "asset_id": "uuid",
    "url": "https://..."
  },
  "opening_text": "Kepada Bapak/Ibu/Saudara/i"
}
```

### opening
```json
{
  "title": "Assalamu'alaikum Wr. Wb.",
  "body": "Dengan memohon rahmat dan ridho Allah SWT..."
}
```

### couple
```json
{
  "groom": {
    "full_name": "Ahmad Fulan",
    "nickname": "Ahmad",
    "father_name": "Bapak ...",
    "mother_name": "Ibu ...",
    "photo": {
      "asset_id": "uuid",
      "url": "https://..."
    },
    "instagram": "ahmad"
  },
  "bride": {
    "full_name": "Siti Fulanah",
    "nickname": "Siti",
    "father_name": "Bapak ...",
    "mother_name": "Ibu ...",
    "photo": {
      "asset_id": "uuid",
      "url": "https://..."
    },
    "instagram": "siti"
  }
}
```

### quote
```json
{
  "text": "Dan di antara tanda-tanda kekuasaan-Nya...",
  "source": "QS. Ar-Rum: 21"
}
```

### events
```json
{
  "items": [
    {
      "type": "akad",
      "title": "Akad Nikah",
      "date": "2026-09-12",
      "start_time": "08:00",
      "end_time": "10:00",
      "timezone": "Asia/Jakarta",
      "venue_name": "Gedung Mawar",
      "address": "Jl. Contoh No. 1, Jakarta",
      "maps_url": "https://maps.google.com/...",
      "note": "Busana bebas rapi"
    },
    {
      "type": "resepsi",
      "title": "Resepsi",
      "date": "2026-09-12",
      "start_time": "11:00",
      "end_time": "14:00",
      "timezone": "Asia/Jakarta",
      "venue_name": "Gedung Mawar",
      "address": "Jl. Contoh No. 1, Jakarta",
      "maps_url": "https://maps.google.com/...",
      "note": null
    }
  ]
}
```

### countdown
```json
{
  "target_date": "2026-09-12T08:00:00+07:00",
  "label": "Menuju Hari Bahagia"
}
```

### location
```json
{
  "title": "Lokasi Acara",
  "venue_name": "Gedung Mawar",
  "address": "Jl. Contoh No. 1, Jakarta",
  "maps_url": "https://maps.google.com/...",
  "embed_url": "https://www.google.com/maps/embed?...",
  "show_map_embed": true
}
```

### love_story
```json
{
  "items": [
    {
      "title": "Pertama Bertemu",
      "date": "2022-01-10",
      "description": "Kami bertemu untuk pertama kali..."
    },
    {
      "title": "Lamaran",
      "date": "2025-12-01",
      "description": "Dengan restu keluarga..."
    }
  ]
}
```

### gallery
```json
{
  "layout": "grid",
  "items": [
    {
      "asset_id": "uuid",
      "url": "https://...",
      "caption": "Prewedding 1",
      "sort_order": 1
    }
  ]
}
```

### video
```json
{
  "provider": "youtube",
  "url": "https://youtube.com/...",
  "embed_url": "https://www.youtube.com/embed/...",
  "title": "Prewedding Video"
}
```

### rsvp
```json
{
  "is_open": true,
  "deadline": "2026-09-05T23:59:59+07:00",
  "max_guests_per_response": 2,
  "collect_attendance_count": true,
  "collect_message": true,
  "collect_meal_preference": false,
  "success_message": "Terima kasih atas konfirmasi kehadiran Anda"
}
```

### wishes
```json
{
  "is_enabled": true,
  "moderation_mode": "post_moderated",
  "allow_anonymous": false,
  "empty_state_text": "Jadilah yang pertama mengirim doa terbaik"
}
```

### gift
```json
{
  "items": [
    {
      "type": "bank_transfer",
      "bank_name": "BCA",
      "account_name": "Ahmad",
      "account_number": "1234567890",
      "note": null
    },
    {
      "type": "ewallet",
      "provider": "GoPay",
      "phone_number": "08123456789",
      "note": null
    }
  ]
}
```

### live_streaming
```json
{
  "provider": "youtube",
  "url": "https://youtube.com/...",
  "embed_url": "https://www.youtube.com/embed/...",
  "schedule_note": "Streaming dimulai 15 menit sebelum akad"
}
```

### additional_info
```json
{
  "items": [
    {
      "title": "Dress Code",
      "description": "Nuansa earth tone"
    },
    {
      "title": "Parking",
      "description": "Area parkir tersedia di basement"
    }
  ]
}
```

### closing
```json
{
  "title": "Terima Kasih",
  "body": "Merupakan suatu kehormatan dan kebahagiaan bagi kami...",
  "signature": "Kami yang berbahagia"
}
```

---

## `style_json` Purpose
`style_json` should only contain local section presentation overrides.
Examples:
- text alignment
- overlay opacity
- decorative ornament toggle
- background color override
- spacing modifier

Do not duplicate content fields in `style_json`.
Do not store template-global style there unless the override is truly section-specific.

Example:
```json
{
  "text_align": "center",
  "theme_accent": "gold",
  "overlay_opacity": 0.4,
  "ornament_enabled": true
}
```

---

## Validation Strategy
Validation must happen at 3 levels:

### 1. Schema-level validation
Ensures payload shape is structurally correct.
Example:
- `gallery.items` must be an array
- `events.items` must be an array
- `rsvp.is_open` must be boolean

### 2. Section-level business validation
Ensures content is meaningful.
Example:
- `cover` should have at least bride/groom names or headline configured
- `events` must have at least one item if enabled
- `location` should have address or maps URL if enabled
- `gift` must have at least one item if enabled

### 3. Invitation publish validation
Ensures entire invitation is publishable.
Example:
- all required sections complete
- slug valid and unique
- event dates valid
- there is at least one visible event or clear schedule representation

---

## Completion Status Rules
Recommended status meanings:
- `empty` = no meaningful data entered
- `incomplete` = partial data exists but required fields missing
- `complete` = good enough for publish requirements at section level
- `warning` = renderable, but recommended data missing
- `error` = structurally invalid or failed validation

Example rules:
- `gallery` enabled with one image may be `complete`
- `gallery` enabled with zero images may be `warning` or `incomplete` depending on template behavior
- `rsvp` enabled but no success message can still be `complete` if success message is optional
- `events` with invalid date format is `error`

---

## Migration Strategy From Old Structure
If the current system stores invitation content in a monolithic structure, migrate as follows:

### Phase 1
- add new tables
- keep old columns/json intact
- create backfill command to generate `invitation_sections`
- do not delete old data yet

### Phase 2
- editor reads from `invitation_sections`
- public renderer may read from transformed source or fallback adapter
- compare output to old renderer for verification

### Phase 3
- once stable, freeze writes to old monolithic structure
- retain read-only compatibility temporarily

### Phase 4
- remove legacy storage only after migration confidence is high

Important:
- migration must be idempotent where possible
- never overwrite user data blindly during backfill
- log invitation ids that fail transformation

---

## Backfill Rules
When generating `invitation_sections` from old data:
- map known old content blocks to stable `section_key`
- preserve original text even if variant differs
- fill missing optional sections only if template default requires initialization
- set `completion_status` conservatively, prefer `incomplete` over `complete` if uncertain
- keep an audit trail if bulk migration is run in production

---

## Template Switch Rules
When invitation changes template:
- keep section rows by semantic `section_type` when possible
- retain `section_key` if still valid
- if previous variant unsupported, replace with new default variant
- preserve `data_json` fields that still match new section shape
- move incompatible fields into `meta_json.previous_payload` only if necessary
- mark affected sections with `warning`

Never silently discard data without traceability.

---

## Edge Cases

### 1. New section added to a template after invitations already exist
Behavior:
- existing invitations should not break
- new section may be inserted on sync with default disabled/enabled behavior based on rules
- sync must be explicit or version-based

### 2. Section removed from template
Behavior:
- old invitations should still render archived/deprecated section safely if data exists
- do not hard-delete invitation section rows automatically

### 3. Variant deprecated
Behavior:
- existing invitations keep rendering
- editor may show notice to migrate variant

### 4. Corrupted `data_json`
Behavior:
- mark section `error`
- keep raw payload for recovery
- editor should show safe fallback state, not white screen

### 5. Duplicate `section_key` due to bad migration or race condition
Behavior:
- database unique constraint should block this
- service layer should catch and retry gracefully

### 6. Required section disabled by stale client payload
Behavior:
- reject update or auto-correct server-side

### 7. `variant_id` points to wrong `section_type`
Behavior:
- reject save
- do not allow `gallery` to use `cover` variant

### 8. Invitation template changed but `template_section_id` remains stale
Behavior:
- allow nullable link
- rely on `section_type` + `section_key` fallback resolution

### 9. User edits in two tabs
Behavior:
- use `updated_at` or explicit version field for optimistic concurrency
- warn on overwrite risk

### 10. Section order gaps or duplicates
Behavior:
- normalize order server-side on save or sync
- do not trust client order blindly

### 11. Published invitation references disabled section in old renderer
Behavior:
- renderer must always check `is_enabled` and visibility state

### 12. Template defaults changed after invitation customization
Behavior:
- existing invitation custom values must win
- template defaults only apply when invitation field is unset

---

## Suggested Laravel Models
Recommended models:
- `Template`
- `TemplateSection`
- `SectionVariant`
- `Invitation`
- `InvitationSection`
- optional `InvitationAsset`
- optional `InvitationEditorDraft`

Recommended casts for JSON columns:
- `meta_json` => `array`
- `schema_json` => `array`
- `ui_meta_json` => `array`
- `default_data_json` => `array`
- `default_style_json` => `array`
- `rules_json` => `array`
- `visibility_conditions_json` => `array`
- `validation_errors_json` => `array`
- `data_json` => `array`
- `style_json` => `array`

---

## Service Layer Suggestions
Recommended service classes:
- `InvitationSectionSyncService`
- `InvitationSectionValidationService`
- `InvitationTemplateSwitchService`
- `InvitationPublishReadinessService`
- `InvitationEditorBackfillService`
- `InvitationSectionOrderingService`

These should contain business logic rather than stuffing it into controllers.

---

## API / Frontend Contract
Editor payload from backend should ideally include:
- invitation core info
- template info
- ordered section list
- per-section variant info
- completion status
- validation summary
- editor UI hints

Example response shape:
```json
{
  "invitation": {
    "id": "uuid",
    "title": "Ahmad & Siti",
    "status": "draft",
    "slug": "ahmad-siti"
  },
  "template": {
    "id": "uuid",
    "code": "sekar_arum",
    "name": "Sekar Arum",
    "version": 2
  },
  "sections": [
    {
      "id": "uuid",
      "section_key": "cover",
      "section_type": "cover",
      "label": "Cover",
      "is_enabled": true,
      "is_required": true,
      "sort_order": 1,
      "completion_status": "complete",
      "variant": {
        "id": "uuid",
        "code": "cover.classic_centered",
        "name": "Classic Centered"
      },
      "data": {},
      "style": {},
      "validation_errors": []
    }
  ]
}
```

---

## Migration Checklist
Implementation should include:
1. create `section_variants` table
2. create `template_sections` table
3. create `invitation_sections` table
4. update `invitations` table with minimal invitation-level fields if needed
5. add foreign keys and indexes
6. seed template blueprint data
7. seed section variants
8. build backfill command for existing invitations
9. build validation service
10. build sync service to initialize sections for new invitations

---

## Minimum Acceptance Criteria
This data model implementation is successful when:
1. every invitation can persist section-level data independently
2. new sections can be added without changing the `invitations` schema each time
3. template defaults and invitation overrides are clearly separated
4. section ordering and enable/disable state are persisted
5. variant selection is persisted safely
6. migration from old data can happen without major data loss
7. invalid payloads do not break the editor or renderer

---

## Final Recommendation
Yes, because there are new sections and the editor is now section-based, the implementation should include new tables.

At minimum, add:
- `section_variants`
- `template_sections`
- `invitation_sections`

If you want recovery and media lifecycle support, also add:
- `invitation_assets`
- `invitation_editor_drafts`

This is the most scalable path for TheDay’s template engine architecture and future section expansion.
