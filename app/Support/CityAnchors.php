<?php

namespace App\Support;

use Database\Seeders\EventSeeder;

/**
 * The seeder jitters every event ±0.5° around one of a fixed set of city
 * anchors. We mirror that list here, labelled with city, country and IANA
 * timezone, so we can turn raw coordinates back into a human-readable place
 * and a sensible local time — entirely offline, no external geocoding API.
 *
 * @see EventSeeder::CITY_ANCHORS
 */
final class CityAnchors
{
    /**
     * @var list<array{lat: float, lng: float, city: string, country: string, tz: string}>
     */
    public const ANCHORS = [
        // United States
        ['lat' => 40.7128, 'lng' => -74.0060, 'city' => 'New York', 'country' => 'US', 'tz' => 'America/New_York'],
        ['lat' => 34.0522, 'lng' => -118.2437, 'city' => 'Los Angeles', 'country' => 'US', 'tz' => 'America/Los_Angeles'],
        ['lat' => 41.8781, 'lng' => -87.6298, 'city' => 'Chicago', 'country' => 'US', 'tz' => 'America/Chicago'],
        ['lat' => 29.7604, 'lng' => -95.3698, 'city' => 'Houston', 'country' => 'US', 'tz' => 'America/Chicago'],
        ['lat' => 33.4484, 'lng' => -112.0740, 'city' => 'Phoenix', 'country' => 'US', 'tz' => 'America/Phoenix'],
        ['lat' => 39.9526, 'lng' => -75.1652, 'city' => 'Philadelphia', 'country' => 'US', 'tz' => 'America/New_York'],
        ['lat' => 29.4241, 'lng' => -98.4936, 'city' => 'San Antonio', 'country' => 'US', 'tz' => 'America/Chicago'],
        ['lat' => 32.7157, 'lng' => -117.1611, 'city' => 'San Diego', 'country' => 'US', 'tz' => 'America/Los_Angeles'],
        ['lat' => 32.7767, 'lng' => -96.7970, 'city' => 'Dallas', 'country' => 'US', 'tz' => 'America/Chicago'],
        ['lat' => 37.3382, 'lng' => -121.8863, 'city' => 'San Jose', 'country' => 'US', 'tz' => 'America/Los_Angeles'],
        ['lat' => 30.2672, 'lng' => -97.7431, 'city' => 'Austin', 'country' => 'US', 'tz' => 'America/Chicago'],
        ['lat' => 37.7749, 'lng' => -122.4194, 'city' => 'San Francisco', 'country' => 'US', 'tz' => 'America/Los_Angeles'],
        ['lat' => 47.6062, 'lng' => -122.3321, 'city' => 'Seattle', 'country' => 'US', 'tz' => 'America/Los_Angeles'],
        ['lat' => 39.7392, 'lng' => -104.9903, 'city' => 'Denver', 'country' => 'US', 'tz' => 'America/Denver'],
        ['lat' => 42.3601, 'lng' => -71.0589, 'city' => 'Boston', 'country' => 'US', 'tz' => 'America/New_York'],
        ['lat' => 36.1699, 'lng' => -115.1398, 'city' => 'Las Vegas', 'country' => 'US', 'tz' => 'America/Los_Angeles'],
        ['lat' => 25.7617, 'lng' => -80.1918, 'city' => 'Miami', 'country' => 'US', 'tz' => 'America/New_York'],
        ['lat' => 33.7490, 'lng' => -84.3880, 'city' => 'Atlanta', 'country' => 'US', 'tz' => 'America/New_York'],
        ['lat' => 38.9072, 'lng' => -77.0369, 'city' => 'Washington', 'country' => 'US', 'tz' => 'America/New_York'],
        ['lat' => 36.1627, 'lng' => -86.7816, 'city' => 'Nashville', 'country' => 'US', 'tz' => 'America/Chicago'],
        ['lat' => 45.5152, 'lng' => -122.6784, 'city' => 'Portland', 'country' => 'US', 'tz' => 'America/Los_Angeles'],
        ['lat' => 29.9511, 'lng' => -90.0715, 'city' => 'New Orleans', 'country' => 'US', 'tz' => 'America/Chicago'],
        // Canada
        ['lat' => 43.6532, 'lng' => -79.3832, 'city' => 'Toronto', 'country' => 'CA', 'tz' => 'America/Toronto'],
        ['lat' => 45.5019, 'lng' => -73.5674, 'city' => 'Montreal', 'country' => 'CA', 'tz' => 'America/Toronto'],
        ['lat' => 49.2827, 'lng' => -123.1207, 'city' => 'Vancouver', 'country' => 'CA', 'tz' => 'America/Vancouver'],
        ['lat' => 51.0447, 'lng' => -114.0719, 'city' => 'Calgary', 'country' => 'CA', 'tz' => 'America/Edmonton'],
        ['lat' => 45.4215, 'lng' => -75.6972, 'city' => 'Ottawa', 'country' => 'CA', 'tz' => 'America/Toronto'],
        ['lat' => 53.5461, 'lng' => -113.4938, 'city' => 'Edmonton', 'country' => 'CA', 'tz' => 'America/Edmonton'],
        ['lat' => 46.8139, 'lng' => -71.2080, 'city' => 'Quebec City', 'country' => 'CA', 'tz' => 'America/Toronto'],
        ['lat' => 49.8951, 'lng' => -97.1384, 'city' => 'Winnipeg', 'country' => 'CA', 'tz' => 'America/Winnipeg'],
        // Mexico
        ['lat' => 19.4326, 'lng' => -99.1332, 'city' => 'Mexico City', 'country' => 'MX', 'tz' => 'America/Mexico_City'],
        ['lat' => 20.6597, 'lng' => -103.3496, 'city' => 'Guadalajara', 'country' => 'MX', 'tz' => 'America/Mexico_City'],
        ['lat' => 25.6866, 'lng' => -100.3161, 'city' => 'Monterrey', 'country' => 'MX', 'tz' => 'America/Monterrey'],
        ['lat' => 19.0414, 'lng' => -98.2063, 'city' => 'Puebla', 'country' => 'MX', 'tz' => 'America/Mexico_City'],
        ['lat' => 32.5149, 'lng' => -117.0382, 'city' => 'Tijuana', 'country' => 'MX', 'tz' => 'America/Tijuana'],
        ['lat' => 21.1619, 'lng' => -86.8515, 'city' => 'Cancún', 'country' => 'MX', 'tz' => 'America/Cancun'],
        ['lat' => 20.9674, 'lng' => -89.5926, 'city' => 'Mérida', 'country' => 'MX', 'tz' => 'America/Merida'],
        // Europe
        ['lat' => 51.5074, 'lng' => -0.1278, 'city' => 'London', 'country' => 'GB', 'tz' => 'Europe/London'],
        ['lat' => 48.8566, 'lng' => 2.3522, 'city' => 'Paris', 'country' => 'FR', 'tz' => 'Europe/Paris'],
        ['lat' => 52.5200, 'lng' => 13.4050, 'city' => 'Berlin', 'country' => 'DE', 'tz' => 'Europe/Berlin'],
        ['lat' => 40.4168, 'lng' => -3.7038, 'city' => 'Madrid', 'country' => 'ES', 'tz' => 'Europe/Madrid'],
        ['lat' => 41.9028, 'lng' => 12.4964, 'city' => 'Rome', 'country' => 'IT', 'tz' => 'Europe/Rome'],
        ['lat' => 52.3676, 'lng' => 4.9041, 'city' => 'Amsterdam', 'country' => 'NL', 'tz' => 'Europe/Amsterdam'],
        ['lat' => 41.3851, 'lng' => 2.1734, 'city' => 'Barcelona', 'country' => 'ES', 'tz' => 'Europe/Madrid'],
        ['lat' => 48.1351, 'lng' => 11.5820, 'city' => 'Munich', 'country' => 'DE', 'tz' => 'Europe/Berlin'],
        ['lat' => 45.4642, 'lng' => 9.1900, 'city' => 'Milan', 'country' => 'IT', 'tz' => 'Europe/Rome'],
        ['lat' => 48.2082, 'lng' => 16.3738, 'city' => 'Vienna', 'country' => 'AT', 'tz' => 'Europe/Vienna'],
        ['lat' => 50.0755, 'lng' => 14.4378, 'city' => 'Prague', 'country' => 'CZ', 'tz' => 'Europe/Prague'],
        ['lat' => 38.7223, 'lng' => -9.1393, 'city' => 'Lisbon', 'country' => 'PT', 'tz' => 'Europe/Lisbon'],
        ['lat' => 53.3498, 'lng' => -6.2603, 'city' => 'Dublin', 'country' => 'IE', 'tz' => 'Europe/Dublin'],
        ['lat' => 55.6761, 'lng' => 12.5683, 'city' => 'Copenhagen', 'country' => 'DK', 'tz' => 'Europe/Copenhagen'],
        ['lat' => 59.3293, 'lng' => 18.0686, 'city' => 'Stockholm', 'country' => 'SE', 'tz' => 'Europe/Stockholm'],
        ['lat' => 59.9139, 'lng' => 10.7522, 'city' => 'Oslo', 'country' => 'NO', 'tz' => 'Europe/Oslo'],
        ['lat' => 60.1699, 'lng' => 24.9384, 'city' => 'Helsinki', 'country' => 'FI', 'tz' => 'Europe/Helsinki'],
        ['lat' => 50.8503, 'lng' => 4.3517, 'city' => 'Brussels', 'country' => 'BE', 'tz' => 'Europe/Brussels'],
        ['lat' => 47.3769, 'lng' => 8.5417, 'city' => 'Zurich', 'country' => 'CH', 'tz' => 'Europe/Zurich'],
        ['lat' => 52.2297, 'lng' => 21.0122, 'city' => 'Warsaw', 'country' => 'PL', 'tz' => 'Europe/Warsaw'],
        ['lat' => 47.4979, 'lng' => 19.0402, 'city' => 'Budapest', 'country' => 'HU', 'tz' => 'Europe/Budapest'],
        ['lat' => 37.9838, 'lng' => 23.7275, 'city' => 'Athens', 'country' => 'GR', 'tz' => 'Europe/Athens'],
        ['lat' => 45.7640, 'lng' => 4.8357, 'city' => 'Lyon', 'country' => 'FR', 'tz' => 'Europe/Paris'],
        ['lat' => 53.5511, 'lng' => 9.9937, 'city' => 'Hamburg', 'country' => 'DE', 'tz' => 'Europe/Berlin'],
        ['lat' => 53.4808, 'lng' => -2.2426, 'city' => 'Manchester', 'country' => 'GB', 'tz' => 'Europe/London'],
        ['lat' => 55.9533, 'lng' => -3.1883, 'city' => 'Edinburgh', 'country' => 'GB', 'tz' => 'Europe/London'],
        ['lat' => 50.1109, 'lng' => 8.6821, 'city' => 'Frankfurt', 'country' => 'DE', 'tz' => 'Europe/Berlin'],
        ['lat' => 50.0647, 'lng' => 19.9450, 'city' => 'Kraków', 'country' => 'PL', 'tz' => 'Europe/Warsaw'],
        ['lat' => 41.1579, 'lng' => -8.6291, 'city' => 'Porto', 'country' => 'PT', 'tz' => 'Europe/Lisbon'],
        ['lat' => 40.8518, 'lng' => 14.2681, 'city' => 'Naples', 'country' => 'IT', 'tz' => 'Europe/Rome'],
        // Global hubs
        ['lat' => 35.6762, 'lng' => 139.6503, 'city' => 'Tokyo', 'country' => 'JP', 'tz' => 'Asia/Tokyo'],
        ['lat' => 37.5665, 'lng' => 126.9780, 'city' => 'Seoul', 'country' => 'KR', 'tz' => 'Asia/Seoul'],
        ['lat' => 1.3521, 'lng' => 103.8198, 'city' => 'Singapore', 'country' => 'SG', 'tz' => 'Asia/Singapore'],
        ['lat' => -33.8688, 'lng' => 151.2093, 'city' => 'Sydney', 'country' => 'AU', 'tz' => 'Australia/Sydney'],
        ['lat' => -37.8136, 'lng' => 144.9631, 'city' => 'Melbourne', 'country' => 'AU', 'tz' => 'Australia/Melbourne'],
        ['lat' => 25.2048, 'lng' => 55.2708, 'city' => 'Dubai', 'country' => 'AE', 'tz' => 'Asia/Dubai'],
        ['lat' => -23.5505, 'lng' => -46.6333, 'city' => 'São Paulo', 'country' => 'BR', 'tz' => 'America/Sao_Paulo'],
        ['lat' => -34.6037, 'lng' => -58.3816, 'city' => 'Buenos Aires', 'country' => 'AR', 'tz' => 'America/Argentina/Buenos_Aires'],
    ];

    /**
     * Full country names keyed by the ISO-3166 alpha-2 codes used above.
     *
     * @var array<string, string>
     */
    public const COUNTRIES = [
        'US' => 'United States',
        'CA' => 'Canada',
        'MX' => 'Mexico',
        'GB' => 'United Kingdom',
        'FR' => 'France',
        'DE' => 'Germany',
        'ES' => 'Spain',
        'IT' => 'Italy',
        'NL' => 'Netherlands',
        'AT' => 'Austria',
        'CZ' => 'Czechia',
        'PT' => 'Portugal',
        'IE' => 'Ireland',
        'DK' => 'Denmark',
        'SE' => 'Sweden',
        'NO' => 'Norway',
        'FI' => 'Finland',
        'BE' => 'Belgium',
        'CH' => 'Switzerland',
        'PL' => 'Poland',
        'HU' => 'Hungary',
        'GR' => 'Greece',
        'JP' => 'Japan',
        'KR' => 'South Korea',
        'SG' => 'Singapore',
        'AU' => 'Australia',
        'AE' => 'United Arab Emirates',
        'BR' => 'Brazil',
        'AR' => 'Argentina',
    ];
}
