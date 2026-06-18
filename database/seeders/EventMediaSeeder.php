<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Attaches local images to every event (two or more each) and seeds a sample
 * attendee list onto a subset of upcoming events.
 *
 * Prefers the bundled sample photos in `storage/app/public/events/real`; falls
 * back to the SVG placeholders from `events:make-placeholders` when those are
 * absent. Idempotent — safe to re-run.
 *
 * Run after EventSeeder: php artisan db:seed --class=EventMediaSeeder
 */
class EventMediaSeeder extends Seeder
{
    private const CATEGORIES = ['concert', 'conference', 'meetup', 'workshop', 'festival', 'sports', 'networking', 'exhibition'];

    /** @var array<string, bool> */
    private array $existsCache = [];

    public function run(): void
    {
        // Reset media so re-runs are deterministic (keeps the showcase events).
        DB::table('event_images')->delete();
        DB::table('attendees')->delete();

        $this->command->info('Attaching images to events...');
        $this->attachImages();

        $this->command->info('Seeding sample attendees...');
        $this->seedAttendees();

        $this->command->info('Done.');
    }

    /**
     * @return list<array{path: string, is_primary: bool, sort_order: int}>
     */
    private function pickImages(string $type): array
    {
        $category = in_array($type, self::CATEGORIES, true) ? $type : 'generic';

        return [
            ['path' => $this->resolveStoredPath("{$category}-1"), 'is_primary' => true, 'sort_order' => 0],
            ['path' => $this->resolveStoredPath("{$category}-2"), 'is_primary' => false, 'sort_order' => 1],
            ['path' => $this->resolveStoredPath('generic-'.mt_rand(1, 3)), 'is_primary' => false, 'sort_order' => 2],
        ];
    }

    /** Prefer the generated photo, fall back to the SVG placeholder. */
    private function resolveStoredPath(string $name): string
    {
        $real = "events/real/{$name}.jpg";
        if ($this->existsCache[$real] ??= Storage::exists($real)) {
            return $real;
        }

        return "events/{$name}.svg";
    }

    private function attachImages(): void
    {
        $now = now();

        Event::query()
            ->whereDoesntHave('images')
            ->select('id', 'type')
            ->chunkById(1000, function ($events) use ($now) {
                $rows = [];
                foreach ($events as $event) {
                    foreach ($this->pickImages($event->type) as $pick) {
                        $rows[] = [
                            'event_id' => $event->id,
                            'path' => $pick['path'],
                            'is_primary' => $pick['is_primary'],
                            'sort_order' => $pick['sort_order'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                foreach (array_chunk($rows, 1000) as $batch) {
                    DB::table('event_images')->insert($batch);
                }
            });
    }

    private function seedAttendees(): void
    {
        $events = Event::query()
            ->where('status', 'published')
            ->where('created_time', '>=', now()->timestamp)
            ->inRandomOrder()
            ->limit(150)
            ->pluck('id');

        $now = now();
        $rows = [];
        $n = 0;

        foreach ($events as $eventId) {
            foreach (range(1, mt_rand(1, 6)) as $ignored) {
                $n++;
                $rows[] = [
                    'event_id' => $eventId,
                    'name' => "Sample Attendee {$n}",
                    'email' => "attendee{$n}@example.test",
                    'status' => mt_rand(0, 4) === 0 ? 'interested' : 'going',
                    'confirmed_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 1000) as $batch) {
            DB::table('attendees')->insert($batch);
        }
    }
}
