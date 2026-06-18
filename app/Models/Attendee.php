<?php

namespace App\Models;

use App\Enums\AttendeeStatus;
use Database\Factories\AttendeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendee extends Model
{
    /** @use HasFactory<AttendeeFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'status' => AttendeeStatus::class,
        'confirmed_at' => 'datetime',
        'reminder_3d_sent_at' => 'datetime',
        'reminder_24h_sent_at' => 'datetime',
    ];

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
