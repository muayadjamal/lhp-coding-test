<?php

use App\Actions\Attendees\RegisterAttendee;
use App\Mail\AttendeeConfirmationMail;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(fn () => Mail::fake());

it('registers a new attendee and queues a confirmation', function () {
    $event = Event::factory()->for(User::factory())->create();

    $result = (new RegisterAttendee)->handle($event, [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.test',
    ]);

    expect($result)->toBe(['already_registered' => false, 'attendees_count' => 1]);
    Mail::assertQueued(AttendeeConfirmationMail::class, 1);
});

it('treats a repeat registration as a no-op', function () {
    $event = Event::factory()->for(User::factory())->create();
    $data = ['name' => 'Ada', 'email' => 'ada@example.test'];

    (new RegisterAttendee)->handle($event, $data);
    $result = (new RegisterAttendee)->handle($event, $data);

    expect($result)->toBe(['already_registered' => true, 'attendees_count' => 1]);
    expect($event->attendees()->count())->toBe(1);
    Mail::assertQueued(AttendeeConfirmationMail::class, 1);
});
