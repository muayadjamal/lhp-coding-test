# Event Visuals

A Laravel + Inertia/Vue app for browsing a large, realistic event dataset two
different ways, registering attendance, and managing everything from a Filament
admin panel.

Built on the provided coding-test skeleton (Laravel 13, Inertia, Vue 3,
Tailwind 4, SQLite, database queue). See [`CODING_TEST.md`](CODING_TEST.md) for
the brief.

---

## What's included

### Two distinct browsing experiences

| Page | Route | Style |
| --- | --- | --- |
| **Visual 1 — Map** | `/events-visual-1` | Full-screen Leaflet map with **server-side clustering** |
| **Visual 2 — Grid** | `/events-visual-2` | Animated, infinite-scroll **card grid** with a featured hero |

Both share one filter bar (date range + location + type + status) and a
register flow, but render the data in deliberately different ways.

**Map (Visual 1).** Markers are clustered **on the server**: the `/events/clusters`
endpoint takes the current viewport (bounding box) + zoom + active filters and
returns either grid-aggregated cluster bubbles (`COUNT`/`AVG` grouped into a
coordinate grid whose cell size shrinks with zoom) or individual markers once
you're zoomed in far enough / the result set is small. This keeps the map fast
even on the full multi-million-row dataset — it never ships more than a few
hundred features. Clustering always reflects the active filters, and choosing a
city/country flies the map there.

**Grid (Visual 2).** Featured events float to the top, cards animate in, images
lazy-load with a hover zoom, and registration happens in a modal. Infinite
scroll pages through `/events/cards`.

### Images, end to end
- `event_images` table, `Event::images()` relation, two or more images per event.
- Served **locally** from the `public` disk (`/storage/...`), never hotlinked.
- Bundled sample photos per category live in `storage/app/public/events/real`;
  an offline SVG generator (`events:make-placeholders`) is the fallback. The
  seeder prefers the photos when present.
- Uploadable from the Filament admin.

### Human-readable locations & timezones
- Events only carry latitude/longitude. An **offline reverse geocoder**
  (`App\Services\Geocoder`) snaps coordinates to the nearest of the seeder's
  city anchors and returns `City, Country` + the IANA timezone — no external API.
- `created_time` (a UTC unix timestamp) is rendered in the **venue's local time**
  with the timezone shown, and delivered to the frontend as both UTC ISO-8601
  and a formatted local string.

### Attendees & emails
- Anyone can register interest/attendance (`POST /events/{event}/attendees`).
- A **confirmation email** is queued on registration.
- **Reminder emails** go out **3 days** and **24 hours** before an event, via the
  `events:send-reminders` command (scheduled hourly). Per-attendee `reminder_*_sent_at`
  columns make it idempotent, and the 24h window is excluded from the 3-day pass
  so an imminent event never gets both at once.

### Filament admin (`/admin`)
- **Events**: searchable/filterable table with image thumbnails, type/status
  badges, start time and going-count; full editor (title/description/type/status/
  schedule/venue/price/coordinates) that reads & writes the JSON `payload`
  without clobbering untouched keys.
- **Images** and **Attendees** relation managers per event (upload images, manage
  the attendee list, see reminder status).
- Standalone **Attendees** resource.

---

## Getting started

Requires PHP 8.3+, Composer, and Node 20+.

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate

# Link the public disk. Sample photos are bundled; run make-placeholders only
# if you want to (re)generate the offline SVG fallback set.
php artisan storage:link
php artisan events:make-placeholders

# Seed a workable dataset (override the row count; default is 1.25M).
SEED_ROWS=4000 php artisan db:seed

npm run build
```

Then run everything (server + queue worker + Vite + logs) with:

```bash
composer dev
```

Visit:
- `/events-visual-1` — map
- `/events-visual-2` — grid
- `/admin` — admin panel (`admin@admin.com` / `123123`)

### Trying the emails

Mail uses the `log` driver and the queue uses the `database` connection by
default, so a queue worker must be running (`composer dev` starts one).

```bash
php artisan events:send-reminders   # queues 3-day / 24-hour reminders
php artisan queue:work --stop-when-empty
# rendered emails appear in storage/logs/laravel.log
```

Several showcase events are seeded 3–7 days out with attendees, so the 3-day
and 24-hour reminders have real candidates to fire for.

---

## Notes & decisions

- **Built for the dataset as-is.** The provided seeder targets up to 1.25M rows.
  Rather than fetch everything, the map clusters server-side over the viewport
  and the grid paginates — both are index-backed (`events_lat_lng_index`,
  `events_created_time_index`). `SEED_ROWS` controls how much to seed locally;
  4–5k is plenty to see clustering behave. (The seeder's insert chunk was reduced
  from 4000 to 2000 so it stays under SQLite's bound-variable limit on the
  default connection.)
- **Title/description live in `payload`.** I didn't reshape the table; the `Event`
  model exposes typed accessors (`title()`, `description()`, `startsAt()`, …) and
  a single `toDisplayArray()` used by both pages, the detail view, and emails.
- **Offline geocoding/timezones.** The seeder jitters events ±0.5° around known
  city anchors, so nearest-anchor matching recovers the city and timezone exactly
  without any network dependency.
- **Location filtering** expands a city/country selection into bounding boxes
  around those anchors (`App\Support\LocationFilter`) — index-friendly and precise.
- **Two pages only.** The site is exactly the two visual pages (grid + map) plus
  a deep-linkable event detail view; the starter's dashboard / welcome / legacy
  listing pages were removed. `/events/cards` serves the grid, `/events/clusters`
  the map, `/events/filters` the shared filter options.
- **Design system.** The public pages follow the Pinterest-style system in
  `DESIGN.md` — Inter type, cream chrome, a single red (`#e60023`) reserved for
  CTAs, 16/32px radii, pin-card grid. Tokens live in `resources/css/app.css` as
  `pin-*` utilities; the chrome is a top nav (no sidebar) via `EventsLayout.vue`.
- **Map tiles** come from CARTO/OpenStreetMap (a standard tile service); only
  *event images* are required to be local, and they are.
- **Validation** on the attendee endpoint returns JSON 422 explicitly, because
  the app only auto-renders JSON exceptions under `api/*`.

---

## Quality

`composer ci:check` runs the full gate:

- **Pint** (formatting) — clean
- **Prettier** + **ESLint** + **vue-tsc** (frontend) — clean
- **PHPStan level 7** (larastan) — 0 errors
- **Pest** — 59 tests

Test coverage focuses on the new behavior: the geocoder, the card/cluster
endpoints and their filters, attendee registration + confirmation email, and the
3-day/24-hour reminder logic (including idempotency).

```bash
php artisan test
```
