<?php

use App\Enums\AttendeeStatus;
use App\Mail\AttendeeConfirmationMail;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the confirmation email with the attendee status label', function () {
    $event = Event::factory()->for(User::factory())->create(['payload' => ['name' => 'Render Check']]);
    $attendee = Attendee::factory()->for($event)->create(['status' => AttendeeStatus::Going->value]);

    // Render guards against passing an enum where the blade expects a string.
    $rendered = (new AttendeeConfirmationMail($attendee))->render();

    expect($rendered)->toContain('Going')->toContain('Render Check');
});
