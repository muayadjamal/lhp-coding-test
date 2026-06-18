<?php

namespace App\Http\Controllers;

use App\Actions\Attendees\RegisterAttendee;
use App\Http\Requests\StoreAttendeeRequest;
use App\Http\Resources\AttendeeRegistrationResource;
use App\Models\Event;

class AttendeeController extends Controller
{
    /**
     * Register interest / attendance for an event and email a confirmation.
     */
    public function store(StoreAttendeeRequest $request, Event $event, RegisterAttendee $action): AttendeeRegistrationResource
    {
        return new AttendeeRegistrationResource(
            $action->handle($event, $request->validated()),
        );
    }
}
