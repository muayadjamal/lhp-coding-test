<?php

namespace App\Http\Controllers;

use App\Actions\Events\ClusterEvents;
use App\Actions\Events\GetEventFilterOptions;
use App\Actions\Events\ListEventCards;
use App\Actions\Events\PickRandomEvent;
use App\Enums\EventStatus;
use App\Http\Resources\EventResource;
use App\Http\Resources\FilterOptionsResource;
use App\Http\Resources\MapClusterResource;
use App\Http\Resources\MapPointResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /**
     * "Surprise me" — jump to a random upcoming, published event's detail page.
     */
    public function random(PickRandomEvent $action): RedirectResponse
    {
        return redirect()->route('events.show', $action->handle());
    }

    /**
     * Filter option lists for the visual pages' filter UIs.
     */
    public function filters(GetEventFilterOptions $action): JsonResponse
    {
        return (new FilterOptionsResource($action->handle()))
            ->response()
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=86400');
    }

    /**
     * Paginated, fully-presented event list for the card grid (Visual 2).
     */
    public function cards(Request $request, ListEventCards $action): JsonResponse
    {
        $start = microtime(true);
        $result = $action->handle($this->filtersFrom($request), max(1, $request->integer('page', 1)));

        return EventResource::collection($result['events'])
            ->additional([
                'current_page' => $result['currentPage'],
                'last_page' => $result['lastPage'],
                'total' => $result['total'],
                'stats' => ['ms' => (int) round((microtime(true) - $start) * 1000)],
            ])
            ->response()
            ->header('Cache-Control', 'public, max-age=15, s-maxage=60');
    }

    /**
     * Server-side clustering for the map (Visual 1). Both modes share one
     * `data` envelope plus a `mode` discriminator; all active filters are
     * applied before aggregating, so clustering reflects the filter state.
     */
    public function clusters(Request $request, ClusterEvents $action): JsonResponse
    {
        $filters = $this->filtersFrom($request) + $request->only(['north', 'south', 'east', 'west']);
        $result = $action->handle($filters, $request->integer('zoom', 3));

        $collection = $result['mode'] === 'points'
            ? MapPointResource::collection($result['items'])
            : MapClusterResource::collection($result['items']);

        return $collection
            ->additional(['mode' => $result['mode'], 'total' => $result['total']])
            ->response()
            ->header('Cache-Control', 'public, max-age=15, s-maxage=30');
    }

    public function show(Event $event): Response
    {
        // Draft (and any non-public) events are not browsable publicly.
        abort_unless(in_array($event->status, EventStatus::browsable(), true), 404);

        $event->load('images')->loadCount('attendees');

        return Inertia::render('Events/Show', ['event' => $event->toDisplayArray()]);
    }

    /**
     * Map the request's query string to the shared filter array.
     *
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
