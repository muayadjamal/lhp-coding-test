<?php

namespace App\Http\Controllers;

use App\Mail\AttendeeConfirmationMail;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AttendeeController extends Controller
{
    /**
     * Register interest / attendance for an event and email a confirmation.
     */
    public function store(Request $request, Event $event): JsonResponse
    {
        // Validate manually and always answer JSON: this is an AJAX-only
        // endpoint, and the app only auto-renders JSON exceptions under api/*.
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'status' => ['nullable', 'in:going,interested'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

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
            'status' => $validated['status'] ?? 'going',
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
