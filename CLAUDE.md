# CLAUDE.md

Guidance for working in this repo. Read alongside `README.md` (user-facing) and
`CODING_TEST.md` (the brief).

## Stack

Laravel 13 · Inertia + Vue 3 · Tailwind 4 · Filament 4 · SQLite · database queue
· Pest · PHPStan (larastan) level 7 · Pint · ESLint/Prettier/vue-tsc.

## Domain model

- **`events`** — provided schema, **not reshaped**. Key columns: `id` (uuid),
  `type`, `status`, `created_time` (UTC unix timestamp = event start),
  `latitude`, `longitude`, `payload` (JSON: `name`, `description`, `venue`,
  `schedule`, `pricing`, plus app flags `featured`/`demo`). Indexed on
  lat/lng and `created_time`.
- **`event_images`** — local images per event (`path` on the `public` disk).
- **`attendees`** — registrations; `reminder_3d_sent_at` / `reminder_24h_sent_at`
  dedupe reminder emails; unique on `(event_id, email)`.

Title/description/etc. live in `payload`. Use the `Event` accessors
(`title()`, `description()`, `startsAt()`, `location()`, `toDisplayArray()`) —
don't read `payload` keys ad hoc. `toDisplayArray()` is the single presentation
contract shared by the Vue pages, the detail view, and the mailables.

## HTTP surface (`routes/web.php`)

- `GET /events-visual-1` → `Events/VisualOne.vue` (map)
- `GET /events-visual-2` → `Events/VisualTwo.vue` (card grid)
- `GET /events/cards` — paginated presented cards (grid) — filters applied
- `GET /events/clusters` — server-side map clustering (bbox + zoom + filters)
- `GET /events/filters` — filter option lists
- `GET /events/{event}` — detail
- `POST /events/{event}/attendees` — register (returns JSON; validates to 422)

`EventController::scopeFilter`-driven filters: `from`, `to`, `status`, `type`,
`city`, `country`, plus map `north/south/east/west`.

## Key services / support

- `App\Services\Geocoder` — offline lat/lng → `{city, country, tz, label}` by
  nearest city anchor. Backed by `App\Support\CityAnchors` (mirrors the seeder's
  anchors, labelled with IANA timezones).
- `App\Support\LocationFilter` — city/country → bounding boxes; filter option lists.

## Commands

- `events:make-placeholders` — offline SVG images on the `public` disk (the
  fallback when the bundled sample photos in `events/real` are absent).
- `events:send-reminders` — 3-day + 24-hour reminders; scheduled hourly in
  `routes/console.php`; idempotent via the `reminder_*_sent_at` columns.

## Seeding

`DatabaseSeeder` → `EventSeeder` (bulk, `SEED_ROWS`, default 1.25M) →
`EventMediaSeeder` (images + sample attendees) → `ShowcaseSeeder` (curated
featured events, several 3–7 days out for reminder testing). Use
`SEED_ROWS=4000` locally.
`EventSeeder` insert chunk is 2000 to stay under SQLite's variable limit.

## Filament (`/admin`)

`app/Filament/Resources/Events` and `.../Attendees`. The Event form binds JSON
`payload` via dot-notation fields; `CreateEvent`/`EditEvent` mutate-hooks merge
into `payload` and convert the `starts_at` picker ↔ `created_time` so other
payload keys are preserved. Login: `admin@admin.com` / `123123`.

## Design system

Public event pages follow `DESIGN.md` (Pinterest-style). Tokens are defined in
`resources/css/app.css` as Tailwind `pin-*` utilities — `bg-pin-red`,
`text-pin-ink`, `rounded-pin-md`/`-lg`, `font-pin` (Inter). Rules: single red
(`#e60023`) for CTAs only; cream chrome (`pin-soft`/`pin-card`/`pin-canvas`);
radii 16px (md) / 32px (lg) / full; no gradients or card shadows. The 2 visuals
+ detail use `EventsLayout.vue` (top nav, no sidebar) via `defineOptions`.

## Conventions / gotchas

- **Default filesystem disk is `public`** (`FILESYSTEM_DISK=public`) and its URL
  is host-relative (`/storage`, see `config/filesystems.php`). Don't pass
  `->disk('public')` explicitly — rely on the default (`Storage::url(...)`,
  Filament `FileUpload`/`ImageColumn` with no disk arg). Images work on any
  host/port and the URL isn't tied to `APP_URL`.
- Frontend shared code lives in `resources/js/lib/events.ts` (types + API +
  CSRF) and `resources/js/components/events/*`.
- The attendee endpoint returns JSON 422 explicitly; the app only auto-JSONs
  exceptions under `api/*` (`bootstrap/app.php`).
- Times: `created_time` is UTC; always present via the venue timezone from the
  geocoder.

## Performance & scale

The public read paths are tuned for high read concurrency on the large
(default 1.25M-row) dataset:

- **Geocoder is a singleton with a per-request coordinate memo** — resolving a
  page of co-located events costs one anchor scan, not one per row. Resolve it
  via DI / `app(Geocoder::class)`; don't `new` it.
- **`cards`** counts the filtered set once per filter (cached, 5 min) instead of
  on every infinite-scroll page, then pages with `forPage` + `get`.
- **`clusters`** runs a single grid aggregate (the total is the sum of cell
  counts — no separate `COUNT(*)`), cached ~30s keyed by zoom + filters +
  viewport so a fresh world view is shared across users.
- **`filters`** is cached (constant per deploy) and served with a long
  `Cache-Control`.
- All three JSON endpoints send `Cache-Control` so browsers/CDN absorb repeat
  traffic — the default unfiltered grid/map is identical for everyone.
- **`random`** seeks a random point on the indexed `created_time` axis instead
  of `ORDER BY RANDOM()` (a full sort).
- Indexes: `status`, `(latitude, longitude)`, `created_time`, plus an
  expression index on `json_extract(payload,'$.featured')` for the featured
  lookup / ordering.
- SQLite runs in **WAL** (`DB_JOURNAL_MODE`/`DB_SYNCHRONOUS`/`DB_BUSY_TIMEOUT`
  in `.env`) so reads run alongside the single writer.

At genuine 100k-concurrent scale the single SQLite file is the ceiling: move
`CACHE_STORE`/`SESSION_DRIVER`/`QUEUE_CONNECTION` to **Redis** and the primary
DB to **Postgres/MySQL with read replicas** (all driver-swappable via `.env`;
the code uses the `Cache` facade and standard Eloquent, no SQLite-isms in the
hot paths beyond the guarded `json_extract` index migration). Run `ANALYZE`
after a large seed so the planner has table statistics.

## Before claiming done

Run `composer ci:check` (Pint + Prettier + ESLint + vue-tsc + PHPStan + Pest).
For UI changes, verify in a browser — `composer dev`, then the two visual pages.
