<?php

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Builds one page of the public card grid: browsable events, featured first
 * then chronological, with a cached total so infinite scroll doesn't re-count.
 */
class ListEventCards
{
    public const PER_PAGE = 24;

    /**
     * @param  array<string, mixed>  $filters
     * @return array{events: Collection<int, Event>, total: int, currentPage: int, lastPage: int}
     */
    public function handle(array $filters, int $page): array
    {
        $base = Event::query()->whereIn('status', EventStatus::browsableValues())->filter($filters);

        // The total is identical for every page of one filter set, so counting
        // it on each request would re-scan the table needlessly. Cache it per
        // filter set; a short TTL keeps it fresh enough for a grid.
        $total = Cache::remember(
            'events:cards:count:'.md5(serialize($filters)),
            now()->addMinutes(5),
            fn (): int => (clone $base)->count(),
        );

        $events = $base
            ->with('images')
            ->withCount('attendees')
            ->orderByDesc(DB::raw('CAST(json_extract(payload, \'$.featured\') AS INTEGER)'))
            ->orderBy('created_time')
            ->forPage($page, self::PER_PAGE)
            ->get();

        return [
            'events' => $events,
            'total' => $total,
            'currentPage' => $page,
            'lastPage' => max(1, (int) ceil($total / self::PER_PAGE)),
        ];
    }
}
