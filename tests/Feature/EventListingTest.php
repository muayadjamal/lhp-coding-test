<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects the home route to the discover grid', function () {
    $this->get(route('home'))->assertRedirect(route('events.visual2'));
});

it('renders the two visual pages without authentication', function () {
    $this->get(route('events.visual1'))->assertOk();
    $this->get(route('events.visual2'))->assertOk();
});

it('returns 404 for a draft event detail', function () {
    $event = Event::factory()->for(User::factory())->create(['status' => 'draft']);

    $this->get(route('events.show', $event))->assertNotFound();
});

it('surprise me redirects to a published event detail', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create([
        'status' => 'published',
        'created_time' => now()->addDays(5)->timestamp,
    ]);

    $this->get(route('events.random'))->assertRedirect(route('events.show', $event));
});

it('shows an event detail page with presented fields', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create([
        'payload' => ['name' => 'Global Tech Summit', 'location' => ['lat' => 1.5, 'lng' => 2.5]],
    ]);

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Show')
            ->where('event.id', $event->id)
            ->where('event.title', 'Global Tech Summit')
        );
});
