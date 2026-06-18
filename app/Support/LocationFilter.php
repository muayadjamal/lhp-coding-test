<?php

namespace App\Support;

/**
 * Translates a city and/or country selection into bounding boxes around the
 * relevant seeded city anchors. Events are jittered ±0.5°, so a ±0.55° box
 * around an anchor captures its events while staying index-friendly.
 */
final class LocationFilter
{
    private const PAD = 0.55;

    /**
     * @return list<array{north: float, south: float, east: float, west: float}>|null
     *                                                                                Null when no location filter is active.
     */
    public static function boxesFor(?string $city, ?string $country): ?array
    {
        if (($city === null || $city === '') && ($country === null || $country === '')) {
            return null;
        }

        $boxes = [];

        foreach (CityAnchors::ANCHORS as $anchor) {
            $cityMatch = $city !== null && $city !== '' && $anchor['city'] === $city;
            $countryMatch = $country !== null && $country !== '' && $anchor['country'] === $country;

            // City filter narrows to one anchor; country filter matches all of
            // its anchors. When both are given, the city wins (it is stricter).
            if ($city !== null && $city !== '') {
                $include = $cityMatch;
            } else {
                $include = $countryMatch;
            }

            if ($include) {
                $boxes[] = [
                    'north' => $anchor['lat'] + self::PAD,
                    'south' => $anchor['lat'] - self::PAD,
                    'east' => $anchor['lng'] + self::PAD,
                    'west' => $anchor['lng'] - self::PAD,
                ];
            }
        }

        return $boxes === [] ? null : $boxes;
    }

    /**
     * Distinct country options for filter UIs: [code => name], sorted by name.
     *
     * @return array<string, string>
     */
    public static function countryOptions(): array
    {
        $codes = array_unique(array_column(CityAnchors::ANCHORS, 'country'));
        $options = [];
        foreach ($codes as $code) {
            $options[$code] = CityAnchors::COUNTRIES[$code];
        }
        asort($options);

        return $options;
    }

    /**
     * Distinct city options for filter UIs, sorted alphabetically.
     *
     * @return list<array{city: string, country: string, lat: float, lng: float}>
     */
    public static function cityOptions(): array
    {
        $cities = array_map(
            fn (array $a) => ['city' => $a['city'], 'country' => $a['country'], 'lat' => $a['lat'], 'lng' => $a['lng']],
            CityAnchors::ANCHORS,
        );

        usort($cities, fn ($a, $b) => strcmp($a['city'], $b['city']));

        return $cities;
    }
}
