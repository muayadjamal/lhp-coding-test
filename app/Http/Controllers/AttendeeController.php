<?php

namespace App\Http\Controllers;

use App\Enums\AttendeeStatus;
use App\Http\Requests\StoreAttendeeRequest;
use App\Mail\AttendeeConfirmationMail;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class AttendeeController extends Controller
{
    /**
     * Register interest / attendance for an event and email a confirmation.
     */
    public function store(StoreAttendeeRequest $request, Event $event): JsonResponse
    {
        $validated = $request->validated();

        // Re-registration must not overwrite the existing record (no proof of
        // email ownership), so treat a known (event, email) pair as a no-op.
        if ($event->attendees()->where('email', $validated['email'])->exists()) {
            return response()->json([
                'ok' => true,
                'already_registered' => true,
                'attendees_count' => $event->attendees()->count(),
            ]);
        }

        $attendee = $event->attendees()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'status' => $validated['status'] ?? AttendeeStatus::Going->value,
            'confirmed_at' => now(),
        ]);

        Mail::to($attendee->email)->queue(new AttendeeConfirmationMail($attendee));

        return response()->json([
            'ok' => true,
            'already_registered' => false,
            'attendees_count' => $event->attendees()->count(),
        ]);
    }
}
