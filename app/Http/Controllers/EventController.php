<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\Geocoder;
use App\Support\LocationFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /** Statuses worth browsing on the public pages. */
    private const STATUSES = ['published', 'sold_out', 'cancelled'];

    private const TYPES = ['concert', 'conference', 'meetup', 'workshop', 'festival', 'sports', 'networking', 'exhibition'];

    private const PER_PAGE = 24;

    /** Below this count (or once zoomed in) the map returns real markers. */
    private const INDIVIDUAL_THRESHOLD = 400;

    /**
     * "Surprise me" — jump to a random upcoming, published event's detail page.
     */
    public function random(): RedirectResponse
    {
        // `ORDER BY RANDOM()` sorts the whole table — fine for a handful of
        // rows, ruinous at a million. Instead pick a random point on the
        // indexed `created_time` axis and grab the next upcoming event, which
        // is an index seek. Approximate uniformity is plenty for "surprise me".
        $now = now()->getTimestamp();
        $bounds = Cache::remember('events:time-bounds', now()->addMinutes(10), fn () => Event::query()
            ->where('status', 'published')
            ->selectRaw('MIN(created_time) as min, MAX(created_time) as max')
            ->toBase()
            ->first());

        $min = $bounds && $bounds->min !== null ? max((int) $bounds->min, $now) : $now;
        $max = $bounds && $bounds->max !== null ? (int) $bounds->max : $now;
        $pivot = $max > $min ? random_int($min, $max) : $min;

        $event = Event::query()
            ->where('status', 'published')
            ->where('created_time', '>=', $pivot)
            ->orderBy('created_time')
            ->first()
            ?? Event::query()
                ->where('status', 'published')
                ->orderByDesc('created_time')
                ->firstOrFail();

        return redirect()->route('events.show', $event);
    }

    /**
     * Filter option lists for the visual pages' filter UIs.
     */
    public function filters(): JsonResponse
    {
        // Option lists derive from compile-time constants, so they only change
        // on deploy: cache them and let browsers/CDN hold them for an hour.
        $options = Cache::remember('events:filter-options', now()->addDay(), fn () => [
            'statuses' => self::STATUSES,
            'types' => self::TYPES,
            'countries' => LocationFilter::countryOptions(),
            'cities' => LocationFilter::cityOptions(),
        ]);

        return response()->json($options)
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=86400');
    }

    /**
     * Paginated, fully-presented event list for the card grid (Visual 2).
     * Featured events float to the top, then chronological order.
     */
    public function cards(Request $request): JsonResponse
    {
        $start = microtime(true);
        $filters = $this->filtersFrom($request);
        $page = max(1, (int) $request->input('page', 1));

        $base = Event::query()->whereIn('status', self::STATUSES)->filter($filters);

        // The total is identical for every page of one filter set, so counting
        // it on each infinite-scroll request would re-scan the table needlessly.
        // Cache it per filter set; a short TTL keeps it fresh enough for a grid.
        $total = Cache::remember(
            'events:cards:count:'.md5(serialize($filters)),
            now()->addMinutes(5),
            fn () => (clone $base)->count(),
        );

        $events = $base
            ->with('images')
            ->withCount('attendees')
            ->orderByDesc(DB::raw('CAST(json_extract(payload, \'$.featured\') AS INTEGER)'))
            ->orderBy('created_time')
            ->forPage($page, self::PER_PAGE)
            ->get();

        $data = $events->map(fn (Event $e) => $e->toDisplayArray())->all();

        return response()->json([
            'data' => $data,
            'current_page' => $page,
            'last_page' => max(1, (int) ceil($total / self::PER_PAGE)),
            'total' => $total,
            'stats' => ['ms' => (int) round((microtime(true) - $start) * 1000)],
        ])->header('Cache-Control', 'public, max-age=15, s-maxage=60');
    }

    /**
     * Server-side clustering for the map (Visual 1). Aggregates events into a
     * coordinate grid sized by zoom so the endpoint stays fast even on the full
     * dataset; returns individual markers once zoomed in far enough. All active
     * filters (date, location, status, type) are applied before aggregating, so
     * clustering always reflects the current filter state.
     */
    public function clusters(Request $request, Geocoder $geocoder): JsonResponse
    {
        $zoom = (int) $request->input('zoom', 3);
        $filters = $this->filtersFrom($request) + $request->only(['north', 'south', 'east', 'west']);

        $base = Event::query()->whereIn('status', self::STATUSES)->filter($filters);

        // Aggregate into a coordinate grid. Cell size (degrees) shrinks with
        // zoom; the divisor is bound as a parameter so the SQL stays a literal
        // string. This single scan also yields the total (sum of cell counts),
        // so we avoid a separate COUNT(*) over the same filtered set.
        //
        // The grid scan is the map's most expensive query (an unindexed FLOOR
        // expression at low zoom can touch the whole table), and a fresh page
        // load or a small pan re-issues the same viewport — so cache it briefly,
        // keyed by zoom + filters + viewport. Many users share the world view.
        $cell = max(0.05, 360.0 / pow(2, $zoom + 1));
        $cacheKey = 'events:clusters:'.md5(serialize([$zoom, $filters]));

        $grid = Cache::remember($cacheKey, now()->addSeconds(30), fn () => (clone $base)
            ->toBase()
            ->selectRaw(
                'COUNT(*) as count, AVG(latitude) as lat, AVG(longitude) as lng, CAST(FLOOR(latitude / ?) AS INTEGER) as gy, CAST(FLOOR(longitude / ?) AS INTEGER) as gx',
                [$cell, $cell],
            )
            ->groupByRaw('gy, gx')
            ->get()
            ->map(fn (object $row) => [
                'lat' => round((float) $row->lat, 5),
                'lng' => round((float) $row->lng, 5),
                'count' => (int) $row->count,
            ])
            ->all());

        $total = array_sum(array_column($grid, 'count'));

        // Zoom in far enough (or few enough events) → return real markers.
        if ($zoom >= 11 || $total <= self::INDIVIDUAL_THRESHOLD) {
            $points = (clone $base)
                ->select('id', 'latitude', 'longitude', 'type', 'created_time', 'payload')
                ->limit(self::INDIVIDUAL_THRESHOLD)
                ->get()
                ->map(function (Event $e) use ($geocoder) {
                    $loc = $geocoder->resolve($e->latitude, $e->longitude);
                    $tz = $loc['tz'] ?? 'UTC';

                    return [
                        'id' => $e->id,
                        'lat' => $e->latitude,
                        'lng' => $e->longitude,
                        'type' => $e->type,
                        'title' => $e->title(),
                        'location_label' => $loc['label'] ?? null,
                        'starts_at_local' => $e->startsAt()?->setTimezone($tz)->format('D, M j · g:i A'),
                        'featured' => (bool) ($e->payload['featured'] ?? false),
                    ];
                })
                ->all();

            return response()->json(['mode' => 'points', 'total' => $total, 'points' => $points])
                ->header('Cache-Control', 'public, max-age=15, s-maxage=30');
        }

        return response()->json(['mode' => 'clusters', 'total' => $total, 'clusters' => $grid])
            ->header('Cache-Control', 'public, max-age=15, s-maxage=30');
    }

    public function show(Event $event, Geocoder $geocoder): Response
    {
        // Draft (and any non-public) events are not browsable publicly.
        abort_unless(in_array($event->status, self::STATUSES, true), 404);

        $event->load('images')->loadCount('attendees');

        return Inertia::render('Events/Show', ['event' => $event->toDisplayArray()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function filtersFrom(Request $request): array
    {
        return array_filter([
            'status' => $request->input('status'),
            'type' => $request->input('type'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'city' => $request->input('city'),
            'country' => $request->input('country'),
        ], fn ($v) => $v !== null && $v !== '');
    }
}
