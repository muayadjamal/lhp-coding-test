<?php

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Support\Facades\Cache;

/**
 * Picks a random upcoming published event for the "Surprise me" link.
 */
class PickRandomEvent
{
    public function handle(): Event
    {
        // `ORDER BY RANDOM()` sorts the whole table — fine for a handful of
        // rows, ruinous at a million. Instead pick a random point on the
        // indexed `created_time` axis and grab the next upcoming event, which
        // is an index seek. Approximate uniformity is plenty for "surprise me".
        $now = now()->getTimestamp();
        $bounds = Cache::remember('events:time-bounds', now()->addMinutes(10), fn () => Event::query()
            ->where('status', EventStatus::Published)
            ->selectRaw('MIN(created_time) as min, MAX(created_time) as max')
            ->toBase()
            ->first());

        $min = $bounds && $bounds->min !== null ? max((int) $bounds->min, $now) : $now;
        $max = $bounds && $bounds->max !== null ? (int) $bounds->max : $now;
        $pivot = $max > $min ? random_int($min, $max) : $min;

        return Event::query()
            ->where('status', EventStatus::Published)
            ->where('created_time', '>=', $pivot)
            ->orderBy('created_time')
            ->first()
            ?? Event::query()
                ->where('status', EventStatus::Published)
                ->orderByDesc('created_time')
                ->firstOrFail();
    }
}
