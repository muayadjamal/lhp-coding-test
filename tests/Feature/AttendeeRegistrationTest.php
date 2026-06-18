<?php

use App\Mail\AttendeeConfirmationMail;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(fn () => Mail::fake());

it('registers an attendee and queues a confirmation email', function () {
    $event = Event::factory()->for(User::factory())->create();

    $this->postJson(route('events.attendees.store', $event), [
        'name' => 'Grace Hopper',
        'email' => 'grace@example.test',
        'status' => 'going',
    ])
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('already_registered', false)
        ->assertJsonPath('attendees_count', 1);

    expect($event->attendees()->where('email', 'grace@example.test')->exists())->toBeTrue();

    Mail::assertQueued(AttendeeConfirmationMail::class, 1);
});

it('does not register the same email twice or resend the confirmation', function () {
    $event = Event::factory()->for(User::factory())->create();
    $payload = ['name' => 'Grace Hopper', 'email' => 'grace@example.test', 'status' => 'going'];

    $this->postJson(route('events.attendees.store', $event), $payload)->assertOk();
    $this->postJson(route('events.attendees.store', $event), $payload)
        ->assertOk()
        ->assertJsonPath('already_registered', true)
        ->assertJsonPath('attendees_count', 1);

    expect($event->attendees()->count())->toBe(1);
    Mail::assertQueued(AttendeeConfirmationMail::class, 1);
});

it('validates the registration input', function () {
    $event = Event::factory()->for(User::factory())->create();

    $this->postJson(route('events.attendees.store', $event), ['name' => '', 'email' => 'not-an-email'])
        ->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['name', 'email']]);
});
