<?php

namespace App\Actions\Attendees;

use App\Enums\AttendeeStatus;
use App\Mail\AttendeeConfirmationMail;
use App\Models\Event;
use Illuminate\Support\Facades\Mail;

/**
 * Registers an attendee for an event and queues their confirmation email.
 */
class RegisterAttendee
{
    /**
     * @param  array<string, mixed>  $data  Validated registration input (name, email, optional status).
     * @return array{already_registered: bool, attendees_count: int}
     */
    public function handle(Event $event, array $data): array
    {
        // Re-registration must not overwrite the existing record (no proof of
        // email ownership), so treat a known (event, email) pair as a no-op.
        if ($event->attendees()->where('email', $data['email'])->exists()) {
            return ['already_registered' => true, 'attendees_count' => $event->attendees()->count()];
        }

        $attendee = $event->attendees()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'status' => $data['status'] ?? AttendeeStatus::Going->value,
            'confirmed_at' => now(),
        ]);

        Mail::to($attendee->email)->queue(new AttendeeConfirmationMail($attendee));

        return ['already_registered' => false, 'attendees_count' => $event->attendees()->count()];
    }
}
