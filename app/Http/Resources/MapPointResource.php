<?php

namespace App\Http\Resources;

use App\Models\Event;
use App\Services\Geocoder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A single event rendered as a map marker, resolved to its local time/place.
 *
 * @mixin Event
 */
class MapPointResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $location = app(Geocoder::class)->resolve($this->latitude, $this->longitude);
        $tz = $location['tz'] ?? 'UTC';

        return [
            'id' => $this->id,
            'lat' => $this->latitude,
            'lng' => $this->longitude,
            'type' => $this->type->value,
            'title' => $this->title(),
            'location_label' => $location['label'] ?? null,
            'starts_at_local' => $this->startsAt()?->setTimezone($tz)->format('D, M j · g:i A'),
            'featured' => (bool) ($this->payload['featured'] ?? false),
        ];
    }
}
