<?php

namespace App\Services;

use App\Support\CityAnchors;

/**
 * Offline reverse geocoder. Resolves a latitude/longitude to its nearest known
 * city anchor and returns a human-readable place plus the local IANA timezone.
 *
 * Events are seeded with a ±0.5° jitter around these anchors, so nearest-anchor
 * matching reliably recovers the intended city without any network calls.
 */
class Geocoder
{
    /**
     * @return array{city: string, country: string, country_name: string, label: string, tz: string}|null
     */
    public function resolve(?float $lat, ?float $lng): ?array
    {
        if ($lat === null || $lng === null) {
            return null;
        }

        $best = null;
        $bestDistance = INF;

        foreach (CityAnchors::ANCHORS as $anchor) {
            // Squared euclidean distance is monotonic and cheap; good enough to
            // pick the nearest anchor over the small jitter radius.
            $dLat = $lat - $anchor['lat'];
            $dLng = $lng - $anchor['lng'];
            $distance = ($dLat * $dLat) + ($dLng * $dLng);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $anchor;
            }
        }

        if ($best === null) {
            return null;
        }

        $countryName = CityAnchors::COUNTRIES[$best['country']];

        return [
            'city' => $best['city'],
            'country' => $best['country'],
            'country_name' => $countryName,
            'label' => "{$best['city']}, {$countryName}",
            'tz' => $best['tz'],
        ];
    }
}
