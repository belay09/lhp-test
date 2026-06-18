# Solution Plan — Event Visuals Coding Test

Working document for implementing `CODING_TEST.md` requirements step by step.

---

## Prerequisites & setup

### Environment
- PHP 8.3+, Composer, Node 22+, npm
- SQLite default (`DB_CONNECTION=sqlite` in `.env.example`)
- Queue: `QUEUE_CONNECTION=database` (already set)
- Mail: `MAIL_MAILER=log` for local dev (emails in `storage/logs/laravel.log`)

### First-time setup
```bash
cp .env.example .env
php artisan key:generate
composer install
npm install
php artisan migrate
SEED_ROWS=50000 php artisan db:seed # dev-friendly; full dataset = 1_250_000
php artisan storage:link # if serving images from storage/app/public
composer dev # server + queue + vite + logs
```

### Dev services to keep running
- **Web**: `php artisan serve` (or `composer dev`)
- **Queue worker**: required for queued emails — `php artisan queue:listen`
- **Scheduler**: required for reminders — `php artisan schedule:work` (local) or cron in prod

### Performance note
Default seed is **1.25M events**. Always filter/paginate in queries. Consider adding DB indexes on `created_time`, `latitude`, `longitude` if filters are slow.

---

## Challenge 1: Event Visuals (two distinct layouts)

### Overview
Build **Event Visuals 1** (`/events-visual-1`) and **Event Visuals 2** (`/events-visual-2`) as two **visually and structurally different** ways to browse events. Each event card/item must show title, description, human-readable location, date/time, and images.

### Suggested layout directions (pick two that feel distinct)
| Page | Suggested approach | Why distinct |
|------|-------------------|--------------|
| Visual 1 | **Responsive card grid** with image carousel | Dense, visual, scroll-friendly |
| Visual 2 | **Map + side list** OR **timeline/calendar** | Spatial or chronological vs grid |

### Sub-tasks

#### 1A. Event images (end-to-end, 2+ per event, local)
**What**: Store and serve multiple images per event from local disk.

**Files to create/modify**:
- `database/migrations/*_create_event_images_table.php` — `event_id`, `path`, `sort_order`
- `app/Models/EventImage.php`
- `app/Models/Event.php` — `images()` HasMany relationship
- `database/seeders/EventSeeder.php` — assign 2–3 placeholder images per event (or post-seed artisan command for existing rows)
- `public/images/events/` — placeholder files (e.g. `concert-1.jpg`, `concert-2.jpg`, reuse across events)
- `EventController` or new API — include `images` in JSON responses

**Approach**:
- Keep image paths in DB; files in `public/images/events/` (simple) or `storage/app/public/events/` + `storage:link`
- Seeder: pick 2+ paths deterministically from event id hash (no per-row file creation)
- Expose URLs via `asset()` or `Storage::url()`

**Acceptance criteria**:
- Every displayed event shows 2+ images
- Image URLs are local (`/images/...` or `/storage/...`), no external hotlinks

**Verify**:
```bash
php artisan migrate
SEED_ROWS=100 php artisan db:seed
# Browse visual pages; inspect network tab for image URLs
```

---

#### 1B. Human-readable addresses (lat/lng → location)
**What**: Convert `latitude`/`longitude` into a city/region string users can filter on.

**Files**:
- `app/Services/GeocodingService.php` (or `Support/ReverseGeocoder.php`)
- Optional migration: `events.address_label` or `events.city` column for caching
- `Event` model accessor: `location_label` or append to API resource

**Approach options** (document choice in DECISIONS.md):
1. **Offline nearest-city lookup** — reuse `CITY_ANCHORS` from `EventSeeder` (fast, no API, good enough for test)
2. **Cached reverse geocode** — Nominatim or similar with rate limiting (more accurate, slower)
3. **Hybrid** — nearest anchor at seed time, store `city` column for filtering

**Acceptance criteria**:
- UI shows e.g. "New York, US" not raw coordinates
- Location filter uses readable place names or regions

**Verify**: Pick events in seeder anchor cities; confirm labels look reasonable.

---

#### 1C. Date & time display (global events)
**What**: Show `schedule.starts_at` / `created_time` in a sensible local format.

**Files**:
- `resources/js/lib/formatEventDate.ts` (or composable `useEventDate.ts`)
- Optionally `Event` API resource with ISO8601 + timezone hint

**Approach**:
- Treat stored unix timestamps as **UTC** (document this)
- Display in **user's browser timezone** via `Intl.DateTimeFormat`
- Show relative hint for near events ("in 3 days") if desired
- Include end time from `payload.schedule.ends_at` where relevant

**Acceptance criteria**:
- Times are human-readable, not raw unix integers
- Decision on timezone handling is documented

**Verify**: Manually check events in different months; toggle system timezone.

---

#### 1D. Filtering (date + location minimum)
**What**: Both visual pages support filtering by date range and location.

**Files**:
- `app/Http/Controllers/EventController.php` — extend `loadListing()` / new `visualData()` endpoint
- `routes/web.php` — e.g. `GET events/visual-data` with `from`, `to`, `location`, `page`
- `resources/js/components/events/EventFilters.vue` (shared)
- `VisualOne.vue`, `VisualTwo.vue`

**Backend filter logic**:
- **Date**: `created_time` or `payload->schedule->starts_at` between `from`/`to` unix bounds — add index on `created_time`
- **Location**: filter by `city` column OR bounding box on lat/lng OR `address_label LIKE %query%`
- Keep **pagination** (50/page like existing endpoint)

**Acceptance criteria**:
- Narrowing date range reduces results
- Location filter returns geographically relevant events
- Filters work on both visual pages (shared component)

**Verify**:
```bash
curl "http://localhost:8000/events/data?status=published&from=2024-01-01"
# After extending endpoint:
curl "http://localhost:8000/events/visual-data?from=2024-06-01&to=2024-06-30&location=London"
php artisan test --filter=EventListing
```

---

#### 1E. Build Visual 1 — Card grid
**Files**: `resources/js/pages/Events/VisualOne.vue`, shared components (`EventCard.vue`, `EventImageGallery.vue`)

**Approach**:
- Fetch filtered paginated data from API
- Card: image carousel, title, truncated description, formatted date, location label
- Tailwind: responsive `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3`
- Animations: `tw-animate-css` fade-in on cards, hover scale on images (subtle)

**Acceptance criteria**: Distinct card-grid experience with all required fields + filters.

---

#### 1F. Build Visual 2 — Different layout (e.g. map or timeline)
**Files**: `resources/js/pages/Events/VisualTwo.vue`, layout-specific components

**Approach examples**:
- **Map view**: Leaflet/MapLibre (add dep) with markers; click marker → event detail panel
- **Timeline**: group events by week/month vertically; sticky date headers

**Must differ from Visual 1** in layout and interaction, not just colors.

**Acceptance criteria**: Second distinct browse UX; same data requirements as Visual 1.

---

#### 1G. Enhance event detail + fix existing bugs
**Files**:
- `resources/js/pages/Events/Show.vue` — proper detail page (not JSON dump)
- `resources/js/pages/Events/Index.vue` — fix `aplyFilters` → `applyFilters`; wire `from` filter in backend if keeping Index

**Acceptance criteria**: `/events/{id}` is user-friendly; optional link from visual pages.

---

## Challenge 2: Attendees & emails

### Overview
Let visitors register interest for an event, maintain an attendee list, send confirmation on signup, and reminder emails 3 days and 24 hours before the event.

### Sub-tasks

#### 2A. Attendee registration
**Files to create**:
- `database/migrations/*_create_event_attendees_table.php` — `event_id`, `name`, `email`, `confirmed_at`, unique `[event_id, email]`
- `app/Models/EventAttendee.php`
- `app/Http/Controllers/EventAttendeeController.php` — `store`
- `app/Http/Requests/StoreEventAttendeeRequest.php`
- `routes/web.php` — `POST events/{event}/attendees`
- `resources/js/components/events/AttendeeForm.vue`
- Wire into `Events/Show.vue` (and optionally visual detail panels)

**Approach**:
- No auth required for registration (unless you want optional login)
- Validate email, prevent duplicates per event
- List attendees on event detail (count + names or paginated)

**Acceptance criteria**:
- Submit form → attendee saved
- Duplicate email for same event rejected gracefully
- Attendee list visible on event page

**Verify**:
```bash
php artisan test --filter=Attendee # after writing tests
# Manual: register on /events/{uuid}, check DB
```

---

#### 2B. Confirmation email
**Files**:
- `app/Mail/EventRegistrationConfirmation.php`
- `resources/views/mail/event-registration.blade.php` (or Markdown mail)
- Dispatch from controller: `Mail::to($email)->queue(new EventRegistrationConfirmation(...))`

**Approach**:
- Use queued mail (`ShouldQueue`) since `QUEUE_CONNECTION=database`
- Include event name, date, location in email body

**Acceptance criteria**:
- After registration, confirmation mail sent (check log driver or Mailhog)

**Verify**:
```bash
# With MAIL_MAILER=log:
tail -f storage/logs/laravel.log
# Or MAIL_MAILER=array in tests:
php artisan test --filter=Confirmation
```

---

#### 2C. Reminder emails (3 days + 24 hours before)
**Files**:
- `app/Mail/EventReminder.php` — accept reminder type (`three_days` | `twenty_four_hours`)
- `database/migrations/*_add_reminder_flags_to_event_attendees.php` — `reminded_3d_at`, `reminded_24h_at` (prevent duplicates)
- `app/Console/Commands/SendEventReminders.php`
- `routes/console.php` or `bootstrap/app.php` — `Schedule::command('events:send-reminders')->hourly()`

**Approach**:
1. Scheduled command runs hourly (or every 15 min)
2. Find events where `starts_at` is in window: now+3days±1h and now+24h±1h
3. For each attendee without `reminded_*_at`, queue reminder mail and stamp column
4. Use `EventAttendee::whereHas('event', ...)` with efficient date queries on `created_time`

**Acceptance criteria**:
- Attendees receive at most one 3-day and one 24-hour reminder each
- Reminders include event details

**Verify**:
```bash
# Create event starting in 3 days, register attendee, run:
php artisan events:send-reminders
# Or fake time in test:
php artisan test --filter=Reminder
php artisan schedule:list
```

---

## Challenge 3: Documentation

### DECISIONS.md (short)
Document choices for:
- Image storage location
- Geocoding strategy
- Timezone handling
- Visual 1 vs Visual 2 layout rationale
- Reminder scheduling window logic
- Any tradeoffs for 1.25M row dataset

---

## Recommended implementation order

| Step | Task | Depends on |
|------|------|------------|
| 1 | Setup env, migrate, seed with `SEED_ROWS=50000` | — |
| 2 | Images: migration, placeholders, seeder, API shape | — |
| 3 | Geocoding: service + cached label on events | — |
| 4 | Date formatting utility (frontend) | — |
| 5 | Extend API: date + location filters, pagination | 2, 3 |
| 6 | Shared `EventFilters.vue` component | 5 |
| 7 | **Visual 1** — card grid | 2–6 |
| 8 | **Visual 2** — map or timeline | 2–6 |
| 9 | Event **Show** page polish | 2–4 |
| 10 | Attendees: migration, form, API | — |
| 11 | Confirmation email (queued) | 10 |
| 12 | Reminder command + schedule + mail | 10, 11 |
| 13 | Animations polish, DECISIONS.md | 7–12 |
| 14 | Feature tests for new behavior | throughout |

---

## Key files reference

| Area | Path |
|------|------|
| Test requirements | `CODING_TEST.md` |
| Routes | `routes/web.php`, `routes/console.php` |
| Event model | `app/Models/Event.php` |
| Event controller | `app/Http/Controllers/EventController.php` |
| Seeder | `database/seeders/EventSeeder.php` |
| Visual pages | `resources/js/pages/Events/VisualOne.vue`, `VisualTwo.vue` |
| Nav | `resources/js/components/AppSidebar.vue` |
| Existing tests | `tests/Feature/EventListingTest.php` |
| Mail config | `config/mail.php`, `.env` |
| Tailwind | `resources/css/app.css` |

---

## Testing checklist (final)

```bash
composer test
npm run types:check
npm run lint:check
npm run build

# Manual smoke test
composer dev
# Visit: /events-visual-1, /events-visual-2
# Filter by date and location
# Open event detail, register attendee
# Check storage/logs for emails
# php artisan events:send-reminders (with test event dates)
```

---

## Notable pre-existing issues to fix during implementation

1. **`Events/Index.vue` line 148**: `@click.prevent="aplyFilters"` should be `applyFilters`
2. **`from` date filter**: passed from controller to frontend but **not used** in `EventController::loadListing()` — only `status` is filtered
3. **No index on `created_time`**: may matter at 1.25M rows when date filtering is added
