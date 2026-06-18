<?php

use App\Mail\EventReminderMail;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(fn () => Mail::fake());

function eventStartingIn(int $seconds): Event
{
    return Event::factory()->for(User::factory())->create([
        'status' => 'published',
        'created_time' => now()->addSeconds($seconds)->timestamp,
    ]);
}

it('sends a 3-day reminder for an event two days out', function () {
    $event = eventStartingIn(2 * 24 * 3600);
    $attendee = Attendee::create(['event_id' => $event->id, 'name' => 'A', 'email' => 'a@example.test', 'status' => 'going']);

    $this->artisan('events:send-reminders')->assertSuccessful();

    Mail::assertQueued(EventReminderMail::class, 1);
    expect($attendee->fresh()->reminder_3d_sent_at)->not->toBeNull()
        ->and($attendee->fresh()->reminder_24h_sent_at)->toBeNull();
});

it('sends the 24-hour reminder for an event a few hours out', function () {
    $event = eventStartingIn(3 * 3600);
    $attendee = Attendee::create(['event_id' => $event->id, 'name' => 'A', 'email' => 'a@example.test', 'status' => 'going']);

    $this->artisan('events:send-reminders')->assertSuccessful();

    Mail::assertQueued(EventReminderMail::class, 1);
    expect($attendee->fresh()->reminder_24h_sent_at)->not->toBeNull();
});

it('is idempotent and does not resend reminders', function () {
    $event = eventStartingIn(2 * 24 * 3600);
    Attendee::create(['event_id' => $event->id, 'name' => 'A', 'email' => 'a@example.test', 'status' => 'going']);

    $this->artisan('events:send-reminders');
    $this->artisan('events:send-reminders');

    Mail::assertQueued(EventReminderMail::class, 1);
});

it('does not remind for events far in the future', function () {
    $event = eventStartingIn(30 * 24 * 3600);
    Attendee::create(['event_id' => $event->id, 'name' => 'A', 'email' => 'a@example.test', 'status' => 'going']);

    $this->artisan('events:send-reminders');

    Mail::assertNothingQueued();
});
