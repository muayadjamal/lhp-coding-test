<?php

use Illuminate\Support\Facades\Schedule;

// Send 3-day and 24-hour attendance reminders. Hourly is granular enough for
// the 24h window while keeping the guards cheap; the sent-at columns dedupe.
Schedule::command('events:send-reminders')->hourly()->withoutOverlapping();
