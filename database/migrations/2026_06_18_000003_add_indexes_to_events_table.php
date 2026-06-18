<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Supports bounding-box queries for the clustered map.
            $table->index(['latitude', 'longitude'], 'events_lat_lng_index');
            // Supports date-range filtering and the default ordering.
            $table->index('created_time', 'events_created_time_index');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_lat_lng_index');
            $table->dropIndex('events_created_time_index');
        });
    }
};
