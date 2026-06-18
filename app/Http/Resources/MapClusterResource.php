<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One aggregated grid cell on the map: an averaged coordinate and a tally.
 */
class MapClusterResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'lat' => $this->resource['lat'],
            'lng' => $this->resource['lng'],
            'count' => $this->resource['count'],
        ];
    }
}
