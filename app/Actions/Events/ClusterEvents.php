<?php

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Server-side map clustering. Aggregates events into a zoom-sized coordinate
 * grid so the endpoint stays fast on the full dataset, and switches to
 * individual markers once zoomed in far enough (or the result set is small).
 */
class ClusterEvents
{
    /** Below this count (or once zoomed in) the map returns real markers. */
    public const INDIVIDUAL_THRESHOLD = 400;

    /**
     * The `items` are Event models in "points" mode and aggregated grid cells
     * in "clusters" mode — the `mode` says which.
     *
     * @param  array<string, mixed>  $filters
     * @return array{mode: string, total: int, items: Collection<int, Event>|array<int, array{lat: float, lng: float, count: int}>}
     */
    public function handle(array $filters, int $zoom): array
    {
        $base = Event::query()->whereIn('status', EventStatus::browsableValues())->filter($filters);

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
            $events = (clone $base)
                ->select('id', 'latitude', 'longitude', 'type', 'created_time', 'payload')
                ->limit(self::INDIVIDUAL_THRESHOLD)
                ->get();

            return ['mode' => 'points', 'total' => $total, 'items' => $events];
        }

        return ['mode' => 'clusters', 'total' => $total, 'items' => $grid];
    }
}
