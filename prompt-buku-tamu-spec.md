# Prompt — TheDay Buku Tamu (Guest Messages) Feature Spec

You are implementing the **Buku Tamu** (Guest Messages) feature for TheDay.

Context:
- TheDay is a premium, mobile-first digital wedding invitation SaaS for Indonesia.
- When guests open an invitation, they can submit a wish/message (ucapan) including their name and message text.
- These messages are displayed publicly on the invitation page in a scrollable feed.
- The couple (invitation owner) needs a way to manage these messages from their dashboard.
- This feature has two sides:
  1. **Public side** — guest submission form on invitation page
  2. **Dashboard side** — couple manages messages from their dashboard

This prompt covers both sides.

---

## Feature Overview

### Public side (invitation page)
- Guests can submit a message with their name and text
- Messages appear in a live scrollable feed on the invitation
- New messages auto-appear without full page reload

### Dashboard side
- Couple can see all messages per invitation
- Couple can hide/show individual messages
- Couple can pin favorite messages
- Couple can delete messages
- Couple can export all messages

---

## Where Buku Tamu Lives in Dashboard
Do not add Buku Tamu as a standalone sidebar menu item.

Instead, Buku Tamu is accessed from inside the invitation detail page.

Flow:
- User opens Undangan Saya
- User clicks on an invitation
- User sees invitation detail page with multiple tabs or sections
- One of the tabs/sections is Buku Tamu

This is because each invitation has its own guest messages.
A user with multiple invitations would have separate Buku Tamu per invitation.

---

## Public Submission Form Spec

### Form fields
- `name` — required, string, max 100 chars
- `message` — required, string, max 500 chars
- `is_anonymous` — optional boolean, if true show name as "Tamu" or "Anonim"

### Submission behavior
- No login required for guests
- After submit, message appears in the feed immediately (optimistic or after save)
- Show success feedback: "Ucapanmu sudah terkirim 🤍"
- Clear form after submit
- Rate limit: max 3 submissions per IP per invitation per hour

### Feed display
- Messages shown in reverse chronological order (newest first)
- Each message shows: name, message text, relative time (2 jam lalu, Kemarin, dll)
- Pinned messages always appear at top
- Hidden messages are not shown on public page
- Animate new message appearing in feed

---

## Dashboard Buku Tamu Spec

### Page layout
- Tab or section inside invitation detail page
- Title: "Buku Tamu"
- Subtitle: "Ucapan dan doa dari tamu undanganmu"

### Summary stats
Show at top:
- Total Ucapan
- Tampil (visible)
- Disembunyikan
- Dipinned

### Message list
Each message item shows:
- guest name
- message text (full or truncated with expand)
- relative time sent
- pinned indicator if pinned
- hidden indicator if hidden
- action buttons: Pin, Sembunyikan/Tampilkan, Hapus

### Filters
- Semua
- Ditampilkan
- Disembunyikan
- Dipinned

### Search
- Search by guest name or message content

### Sort
- Terbaru (default)
- Terlama
- Pinned dulu

---

## Message Moderation Actions

### Pin
- Pinned messages appear at the very top of the public feed
- Only one or multiple messages can be pinned (allow multiple)
- Toggling pin again unpins the message
- Pinned messages still respect hide/show visibility

### Hide (Sembunyikan)
- Hidden messages are not shown on public invitation page
- Hidden messages remain in dashboard list with "Disembunyikan" indicator
- User can unhide (Tampilkan) at any time
- Default: all messages visible (not hidden)

### Delete
- Permanently deletes message
- Requires confirmation dialog
- Cannot be undone
- Message disappears from dashboard and public page

### Bulk actions
- Select multiple messages
- Bulk hide
- Bulk delete
- Bulk unpin

---

## Export Feature
Allow couple to export all messages.

### Export format
- CSV or plain text file
- Columns: Nama, Ucapan, Waktu Kirim, Status (Tampil/Disembunyikan/Pinned)

### Export scope
- All messages (default)
- Only visible messages
- Only pinned messages

### Export trigger
Button: "Export Ucapan" in the Buku Tamu page header.

---

## Data Model

### `guest_messages` table
- `id` UUID primary key
- `invitation_id` UUID foreign key
- `name` string
- `message` text
- `is_anonymous` boolean default false
- `is_hidden` boolean default false
- `is_pinned` boolean default false
- `pinned_at` timestamp nullable
- `hidden_at` timestamp nullable
- `ip_address` string nullable (for rate limiting)
- `user_agent` string nullable
- `created_at`
- `updated_at`
- `deleted_at` nullable (soft delete)

---

## Validation Rules

### Public submission
- `name` required, min 2 chars, max 100 chars
- `message` required, min 5 chars, max 500 chars
- `is_anonymous` optional boolean
- Rate limit: max 3 submissions per IP per invitation per hour
- Do not allow HTML or script injection in name or message fields
- Strip all HTML tags, store plain text only

### Dashboard actions
- Only invitation owner can moderate messages
- Bulk delete must require confirmation
- Cannot hide/delete messages from other user's invitations

---

## Real-time Behavior

### Public page
- New messages appear without full page reload
- Use Laravel Echo + Pusher/Soketi for broadcast
- Broadcast event: `GuestMessageSubmitted` on `invitation.{id}` channel
- Public page listens and prepends new message to feed

### Dashboard
- Optionally show real-time new message counter badge
- Dashboard does not need to auto-update feed in real time (manual refresh acceptable)

---

## Privacy Considerations
- If guest marks `is_anonymous = true`, show name as "Tamu Anonim" on public page
- Store real name in database for couple's reference in dashboard
- Do not expose IP address in dashboard UI
- Messages from hidden/deleted guests should not be re-exposed via API

---

## Edge Cases

### 1. Invitation has no messages yet
- Show empty state in dashboard: "Belum ada ucapan yang masuk"
- Show encouraging copy: "Ucapan tamu akan muncul di sini setelah mereka membuka undangan"

### 2. Guest submits empty name or message
- Block submission with inline validation
- Do not allow anonymous submission without at least a message

### 3. Spam / multiple submissions from same IP
- Rate limit: 3 per hour per IP per invitation
- Show friendly message: "Kamu sudah mengirim ucapan. Terima kasih! 🤍"

### 4. Very long message
- Truncate to 3 lines in feed with "Lihat selengkapnya" expand
- Full message stored in database

### 5. Pinned message is later hidden
- Hidden takes priority over pinned
- Pinned + hidden = not shown on public page
- Still shows in dashboard with both badges

### 6. Invitation owner deletes invitation
- Cascade soft-delete all messages for that invitation

### 7. Messages sent before couple activates moderation
- All messages default to visible
- Couple can retroactively hide any message

### 8. Guest submits message in non-Indonesian language
- Allow any language
- No language filtering

### 9. Export when no messages exist
- Disable export button or show empty file with headers only

### 10. Couple tries to hide all messages
- Allow, no minimum visible message requirement
- Public page shows empty buku tamu section gracefully

### 11. Very high message volume (viral invitation)
- Dashboard list should paginate (20-50 per page)
- Stats still show total counts correctly

---

## UI Components

### Public invitation page
- `GuestMessageForm`
- `GuestMessageFeed`
- `GuestMessageItem`

### Dashboard
- `BukuTamuTab`
- `BukuTamuStats`
- `BukuTamuFilterBar`
- `BukuTamuMessageList`
- `BukuTamuMessageItem`
- `BukuTamuBulkActionBar`
- `BukuTamuEmptyState`
- `BukuTamuExportButton`
- `BukuTamuDeleteConfirmDialog`

---

## API Endpoints

### Public
- `POST /invitations/{slug}/messages` — submit guest message

### Dashboard
- `GET /dashboard/invitations/{id}/messages` — list messages with filter/sort/search
- `PATCH /dashboard/invitations/{id}/messages/{messageId}` — update is_hidden, is_pinned
- `DELETE /dashboard/invitations/{id}/messages/{messageId}` — delete message
- `POST /dashboard/invitations/{id}/messages/bulk` — bulk hide/delete/unpin
- `GET /dashboard/invitations/{id}/messages/export` — export messages

---

## Deliverables
Produce:
1. Laravel migration for `guest_messages` table
2. Laravel model with relationships, casts, scopes
3. Public message submission controller with rate limiting
4. Dashboard messages controller (list, update, delete, bulk, export)
5. Request validation classes
6. GuestMessageSubmitted broadcast event
7. Public page Vue components: form + feed with real-time
8. Dashboard Vue components: Buku Tamu tab with moderation
9. Export CSV logic
10. Edge case handling per spec

---

## Non-Goals
Do not implement:
- Reply to guest messages (future phase)
- Reaction/emoji on messages (future phase)
- Guest message notifications via WhatsApp or email (future phase)
- AI moderation / auto-filter (future phase)

---

## Acceptance Criteria
Implementation is successful when:
1. Guests can submit messages from the public invitation page without login.
2. Messages appear in real-time on the public page.
3. Couple can see all messages in the dashboard Buku Tamu tab.
4. Couple can hide, pin, and delete messages.
5. Bulk actions work for multi-message moderation.
6. Export downloads a complete CSV of all messages.
7. Rate limiting prevents spam submissions.
8. Hidden messages do not appear on the public invitation page.
9. Pinned messages appear at the top of the public feed.
10. The feature feels warm, premium, and emotionally resonant with TheDay brand.
