<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns presented cards with featured events first', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create([
        'status' => 'published',
        'created_time' => now()->addDays(5)->timestamp,
        'latitude' => 40.71, 'longitude' => -74.00,
        'payload' => ['name' => 'Regular Show'],
    ]);
    Event::factory()->for($user)->create([
        'status' => 'published',
        'created_time' => now()->addDays(20)->timestamp,
        'latitude' => 48.85, 'longitude' => 2.35,
        'payload' => ['name' => 'Featured Gala', 'featured' => true],
    ]);

    $response = $this->getJson(route('events.cards'))->assertOk()
        ->assertJsonStructure(['data' => [['id', 'title', 'starts_at_local', 'location_label', 'timezone', 'images', 'attendees_count']]]);

    // Featured floats to the top regardless of date.
    expect($response->json('data.0.title'))->toBe('Featured Gala')
        ->and($response->json('data.0.featured'))->toBeTrue();
});

it('hides draft events from the public grid', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create(['status' => 'draft', 'payload' => ['name' => 'Secret draft']]);
    Event::factory()->for($user)->create(['status' => 'published']);

    $this->getJson(route('events.cards'))
        ->assertOk()
        ->assertJsonPath('total', 1);
});

it('filters cards by type', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create(['type' => 'concert', 'status' => 'published']);
    Event::factory()->for($user)->create(['type' => 'workshop', 'status' => 'published']);

    $this->getJson(route('events.cards', ['type' => 'concert']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.type', 'concert');
});

it('filters cards by country via the location bounding boxes', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create(['latitude' => 40.71, 'longitude' => -74.00]); // New York, US
    Event::factory()->for($user)->create(['latitude' => 35.68, 'longitude' => 139.65]); // Tokyo, JP

    $this->getJson(route('events.cards', ['country' => 'JP']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.country', 'JP');
});

it('filters cards near a point', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create(['latitude' => 40.71, 'longitude' => -74.00]); // New York
    Event::factory()->for($user)->create(['latitude' => 35.68, 'longitude' => 139.65]); // Tokyo

    $this->getJson(route('events.cards', ['near' => '40.71,-74.00']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.city', 'New York');
});

it('filters cards by date range', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create(['created_time' => now()->addDays(2)->timestamp]);
    Event::factory()->for($user)->create(['created_time' => now()->addDays(40)->timestamp]);

    $this->getJson(route('events.cards', ['from' => now()->addDay()->toDateString(), 'to' => now()->addDays(10)->toDateString()]))
        ->assertOk()
        ->assertJsonPath('total', 1);
});
