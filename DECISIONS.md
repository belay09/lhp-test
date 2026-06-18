# Implementation Decisions

Short notes on key choices made while building the Event Visuals pages, attendee flow, and supporting backend.

---

## Images

- **Storage**: Placeholder assets live under `public/images/events/` and are served directly by the web server (no external URLs).
- **Schema**: `event_images` table stores `path`, `sort_order`, and a foreign key to `events`. Paths are relative (e.g. `images/events/concert-1.jpg`).
- **Seeding**: Each event gets **2 or 3 images**, determined deterministically from `crc32($eventId)` so the count and selection are stable across re-seeds without per-row randomness.
- **API**: Events are loaded with `images` eager-loaded; the frontend gallery reads ordered paths from the relationship.

---

## Geocoding (addresses)

- **Strategy**: Offline nearest-city lookup via `GeocodingService` and `CityAnchors` — the same anchor list used when seeding coordinates.
- **Caching**: `location_label` column on `events`, populated at seed time and backfillable via `php artisan events:backfill-location-labels`.
- **Rationale**: No external API calls, predictable performance at scale, and labels align with seeded data. Accuracy is city-level, not street-level — sufficient for browsing and filtering.

---

## Date & time

- **Storage**: Unix timestamps in UTC (`created_time` for event start; optional `payload.schedule.starts_at` / `ends_at` when present).
- **Display**: `formatEventDate.ts` uses `Intl.DateTimeFormat` with the browser's local timezone (no explicit `timeZone` option). Relative hints ("in 3 days") appear for events within seven days.
- **Rationale**: Keeps the database timezone-agnostic; users see times in their own locale without server-side timezone configuration.

---

## Visual 1 vs Visual 2

| | Visual 1 | Visual 2 |
|---|----------|----------|
| **Layout** | Responsive card grid | Vertical timeline grouped by month |
| **Strength** | Image-forward browsing, familiar discovery pattern | Chronological scan, compact list density |
| **Shared** | `EventFilters`, infinite scroll via `/events/data`, same API |

Both pages consume the same paginated JSON endpoint but present events differently so they feel like distinct experiences rather than the same template restyled.

---

## Filtering

- **API params**: `from`, `to`, `location`, `status`, `page` on `GET /events/data`.
- **Date filter**: Applied to `created_time` using UTC day bounds (`startOfDay` / `endOfDay` on parsed dates).
- **Location filter**: `LIKE '%query%'` on `location_label` (case-sensitive per DB collation).
- **UI**: Shared `EventFilters.vue` component on both visual pages and the legacy index.

---

## Attendees & emails

- **Auth**: None — open registration form on the event detail page.
- **Uniqueness**: `UNIQUE(event_id, email)` prevents duplicate sign-ups per event.
- **Confirmation**: `EventRegistrationConfirmation` mailable queued on registration (`Mail::queue`).
- **Guards**: Registration blocked for `sold_out` and `cancelled` events.

---

## Reminder emails

- **Command**: `php artisan events:send-reminders`, scheduled **hourly** in `routes/console.php`.
- **Windows**: Events whose `created_time` falls within ±1 hour of "now + 3 days" (3-day reminder) or "now + 1 day" (24-hour reminder), all in UTC.
- **Idempotency**: `reminded_3d_at` and `reminded_24h_at` on `event_attendees` — only attendees not yet stamped receive mail.
- **Event start**: `created_time` is treated as the event start for reminder timing (matches seeder and listing sort).

---

## Performance (1.25M-row dataset)

- **Indexes**: `created_time` and `location_label` on `events` to support date-range and location filters.
- **Pagination**: 50 events per page on `/events/data`; frontend uses intersection-observer infinite scroll.
- **Eager loading**: `user` and `images` on listing queries to avoid N+1.
- **Dev seeding**: `SEED_ROWS` env var (default `1_250_000`, read via `config/seeding.php`) — use a smaller value (e.g. `50000`) for local development and tests.
- **Seeder**: Chunked bulk inserts (4000 rows/batch) with SQLite pragmas for throughput.

---

## Animations

Light Tailwind `animate-in` transitions on cards and timeline items (fade + slide on load, hover scale on images). Kept subtle to avoid distracting from content on large lists.
