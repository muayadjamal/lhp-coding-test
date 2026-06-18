<?php

namespace App\Actions\Events;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Support\LocationFilter;
use Illuminate\Support\Facades\Cache;

/**
 * The option lists that drive the public filter UIs.
 */
class GetEventFilterOptions
{
    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        // These derive from compile-time enums/anchors, so they only change on
        // deploy: cache them and let browsers/CDN hold them for a while.
        return Cache::remember('events:filter-options', now()->addDay(), fn (): array => [
            'statuses' => EventStatus::browsableValues(),
            'types' => EventType::values(),
            'countries' => LocationFilter::countryOptions(),
            'cities' => LocationFilter::cityOptions(),
        ]);
    }
}
