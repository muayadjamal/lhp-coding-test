<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The card grid orders by the JSON `featured` flag, then `created_time`.
     * Without an index that expression forces a full scan + filesort over the
     * whole filtered set on every page. An expression index matching the sort
     * lets the planner read rows already ordered. SQLite and MySQL 8 both
     * support functional/expression indexes, with slightly different syntax.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement(
                "CREATE INDEX IF NOT EXISTS events_featured_order_index ON events (CAST(json_extract(payload, '\$.featured') AS INTEGER) DESC, created_time)"
            );
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement(
                "CREATE INDEX events_featured_order_index ON events ((CAST(json_extract(`payload`, '\$.featured') AS UNSIGNED)) DESC, created_time)"
            );
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS events_featured_order_index');
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('DROP INDEX events_featured_order_index ON events');
        }
    }
};
