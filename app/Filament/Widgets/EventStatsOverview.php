<?php

namespace App\Filament\Widgets;

use App\Models\Attendee;
use App\Models\Event;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class EventStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Counts over the (large) events table are expensive, so cache them
        // briefly — the dashboard doesn't need to-the-second accuracy.
        $stats = Cache::remember('admin:event-stats', now()->addMinutes(5), fn () => [
            'total' => Event::query()->count(),
            'published' => Event::query()->where('status', 'published')->count(),
            'upcoming' => Event::query()
                ->where('status', 'published')
                ->where('created_time', '>=', now()->getTimestamp())
                ->count(),
            'attendees' => Attendee::query()->count(),
        ]);

        return [
            Stat::make('Total events', number_format($stats['total']))
                ->description('All events in the catalogue')
                ->descriptionIcon(Heroicon::OutlinedRectangleStack)
                ->color('gray'),
            Stat::make('Published', number_format($stats['published']))
                ->description('Live on the public pages')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success'),
            Stat::make('Upcoming', number_format($stats['upcoming']))
                ->description('Published & yet to start')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color('info'),
            Stat::make('Registrations', number_format($stats['attendees']))
                ->description('Attendees across all events')
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->color('warning'),
        ];
    }
}
