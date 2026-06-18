<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\Geocoder;
use App\Support\LocationFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /** Statuses worth browsing on the public pages. */
    private const STATUSES = ['published', 'sold_out', 'cancelled'];

    private const TYPES = ['concert', 'conference', 'meetup', 'workshop', 'festival', 'sports', 'networking', 'exhibition'];

    /**
     * "Surprise me" — jump to a random upcoming, published event's detail page.
     */
    public function random(): RedirectResponse
    {
        $event = Event::query()
            ->where('status', 'published')
            ->where('created_time', '>=', now()->timestamp)
            ->inRandomOrder()
            ->first()
            ?? Event::query()->where('status', 'published')->inRandomOrder()->firstOrFail();

        return redirect()->route('events.show', $event);
    }

    /**
     * Filter option lists for the visual pages' filter UIs.
     */
    public function filters(): JsonResponse
    {
        return response()->json([
            'statuses' => self::STATUSES,
            'types' => self::TYPES,
            'countries' => LocationFilter::countryOptions(),
            'cities' => LocationFilter::cityOptions(),
        ]);
    }

    /**
     * Paginated, fully-presented event list for the card grid (Visual 2).
     * Featured events float to the top, then chronological order.
     */
    public function cards(Request $request): JsonResponse
    {
        $start = microtime(true);

        $events = Event::query()
            ->filter($this->filtersFrom($request))
            ->with('images')
            ->withCount('attendees')
            ->orderByDesc(DB::raw('CAST(json_extract(payload, \'$.featured\') AS INTEGER)'))
            ->orderBy('created_time')
            ->paginate(24)
            ->withQueryString();

        $data = collect($events->items())->map(fn (Event $e) => $e->toDisplayArray())->all();

        return response()->json([
            'data' => $data,
            'current_page' => $events->currentPage(),
            'last_page' => $events->lastPage(),
            'total' => $events->total(),
            'stats' => ['ms' => (int) round((microtime(true) - $start) * 1000)],
        ]);
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

        $base = Event::query()->filter($filters);

        // Zoom in far enough (or few enough events) → return real markers.
        $total = (clone $base)->count();
        $individualThreshold = 400;

        if ($zoom >= 11 || $total <= $individualThreshold) {
            $points = (clone $base)
                ->select('id', 'latitude', 'longitude', 'type', 'created_time', 'payload')
                ->limit($individualThreshold)
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

            return response()->json(['mode' => 'points', 'total' => $total, 'points' => $points]);
        }

        // Otherwise aggregate into a grid. Cell size (degrees) shrinks with zoom;
        // the divisor is bound as a parameter so the SQL stays a literal string.
        $cell = max(0.05, 360.0 / pow(2, $zoom + 1));

        $clusters = (clone $base)
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
            ->all();

        return response()->json(['mode' => 'clusters', 'total' => $total, 'clusters' => $clusters]);
    }

    public function show(Event $event, Geocoder $geocoder): Response
    {
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
            'near' => $request->input('near'),
        ], fn ($v) => $v !== null && $v !== '');
    }
}
