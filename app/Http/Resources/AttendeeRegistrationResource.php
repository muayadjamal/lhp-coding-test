<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The outcome of a registration attempt: whether it succeeded, whether the
 * email was already on the list, and the event's current head count.
 */
class AttendeeRegistrationResource extends JsonResource
{
    /**
     * This is a status payload, not a model envelope — don't wrap it in "data".
     */
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ok' => true,
            'already_registered' => (bool) $this->resource['already_registered'],
            'attendees_count' => (int) $this->resource['attendees_count'],
        ];
    }
}
