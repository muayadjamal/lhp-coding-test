<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            // 'interested' or 'going'.
            $table->string('status')->default('going');
            $table->timestamp('confirmed_at')->nullable();
            // Dedupe guards so each reminder fires at most once per attendee.
            $table->timestamp('reminder_3d_sent_at')->nullable();
            $table->timestamp('reminder_24h_sent_at')->nullable();
            $table->timestamps();

            // One registration per email per event.
            $table->unique(['event_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendees');
    }
};
