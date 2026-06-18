<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns individual points when the result set is small', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create(['latitude' => 40.71, 'longitude' => -74.00, 'payload' => ['name' => 'NYC Show']]);

    $this->getJson(route('events.clusters', ['zoom' => 3]))
        ->assertOk()
        ->assertJsonPath('mode', 'points')
        ->assertJsonStructure(['mode', 'total', 'points' => [['id', 'lat', 'lng', 'type', 'title']]]);
});

it('applies filters before aggregating', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create(['type' => 'concert', 'latitude' => 40.71, 'longitude' => -74.00]);
    Event::factory()->for($user)->create(['type' => 'workshop', 'latitude' => 40.72, 'longitude' => -74.01]);

    $this->getJson(route('events.clusters', ['zoom' => 3, 'type' => 'concert']))
        ->assertOk()
        ->assertJsonPath('total', 1);
});

it('respects an explicit bounding box', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create(['latitude' => 40.71, 'longitude' => -74.00]); // inside
    Event::factory()->for($user)->create(['latitude' => 35.68, 'longitude' => 139.65]); // outside

    $this->getJson(route('events.clusters', [
        'zoom' => 5, 'north' => 41, 'south' => 40, 'east' => -73, 'west' => -75,
    ]))
        ->assertOk()
        ->assertJsonPath('total', 1);
});
