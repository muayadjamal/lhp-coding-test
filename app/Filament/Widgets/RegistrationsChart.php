<?php

namespace App\Filament\Widgets;

use App\Models\Attendee;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class RegistrationsChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Registrations (last 14 days)';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $days = 14;
        $start = CarbonImmutable::today()->subDays($days - 1);

        // One grouped query, then map onto a zero-filled day range so gaps in
        // registration still render as 0 rather than collapsing the axis.
        $counts = Attendee::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('date(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $values = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->addDays($i);
            $labels[] = $date->format('M j');
            $values[] = (int) ($counts[$date->format('Y-m-d')] ?? 0);
        }

        return [
            'datasets' => [[
                'label' => 'Registrations',
                'data' => $values,
                'borderColor' => '#e60023',
                'backgroundColor' => 'rgba(230, 0, 35, 0.1)',
                'fill' => true,
                'tension' => 0.3,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
