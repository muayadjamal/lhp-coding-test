<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use Carbon\CarbonImmutable;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    /** Assemble a complete payload from the form fields for a new event. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] ??= auth()->id() ?? 1;

        $ts = ! empty($data['starts_at'])
            ? CarbonImmutable::parse($data['starts_at'], 'UTC')->getTimestamp()
            : now()->getTimestamp();
        $data['created_time'] = $ts;

        $payload = $data['payload'] ?? [];
        $payload['category'] = $data['type'];
        $payload['location'] = ['lat' => (float) $data['latitude'], 'lng' => (float) $data['longitude']];
        $payload['schedule'] = ['starts_at' => $ts, 'ends_at' => $ts + 10800];
        $payload['pricing'] = array_merge(['currency' => 'USD'], $payload['pricing'] ?? []);
        $data['payload'] = $payload;

        unset($data['starts_at']);

        return $data;
    }
}
