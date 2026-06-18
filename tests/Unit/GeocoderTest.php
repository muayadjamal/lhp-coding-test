<?php

use App\Services\Geocoder;

it('resolves coordinates to the nearest city with timezone', function () {
    $result = (new Geocoder)->resolve(40.71, -74.00); // ~New York

    expect($result)->not->toBeNull()
        ->and($result['city'])->toBe('New York')
        ->and($result['country'])->toBe('US')
        ->and($result['country_name'])->toBe('United States')
        ->and($result['tz'])->toBe('America/New_York')
        ->and($result['label'])->toBe('New York, United States');
});

it('snaps a jittered coordinate to the right anchor', function () {
    $result = (new Geocoder)->resolve(35.30, 139.40); // jittered around Tokyo

    expect($result['city'])->toBe('Tokyo')
        ->and($result['tz'])->toBe('Asia/Tokyo');
});

it('returns null when coordinates are missing', function () {
    expect((new Geocoder)->resolve(null, null))->toBeNull();
});
