<?php

namespace Database\Seeders;

use App\Enums\AttendeeStatus;
use App\Enums\EventStatus;
use App\Models\Attendee;
use App\Models\Event;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * A small set of hand-authored, realistic "featured" events with rich
 * descriptions, real venues and upcoming dates across major cities. These give
 * the front-end something polished to showcase on top of the bulk dataset.
 *
 * Idempotent: existing showcase events (payload.featured = true) are removed
 * and recreated. Run after EventSeeder + EventMediaSeeder.
 */
class ShowcaseSeeder extends Seeder
{
    /**
     * @var list<array{title: string, type: string, city: array{float, float}, in_days: int, venue: string, price: int, desc: string}>
     */
    private const EVENTS = [
        ['title' => 'Neon Skyline · Synthwave Night', 'type' => 'concert', 'city' => [40.7128, -74.0060], 'in_days' => 3, 'venue' => 'Brooklyn Steel', 'price' => 45, 'desc' => 'A night of pulsing synths and retro-futuristic visuals as three headline acts take over Brooklyn. Expect lasers, fog, and a wall-to-wall crowd.'],
        ['title' => 'Frontend Horizons 2026', 'type' => 'conference', 'city' => [37.7749, -122.4194], 'in_days' => 12, 'venue' => 'Moscone West', 'price' => 299, 'desc' => 'Two days of deep-dive talks on modern web architecture, edge rendering, and design systems from the engineers building the tools you use every day.'],
        ['title' => 'Sunset Rooftop Founders Meetup', 'type' => 'meetup', 'city' => [34.0522, -118.2437], 'in_days' => 5, 'venue' => 'The Perch DTLA', 'price' => 0, 'desc' => 'Casual rooftop mixer for early-stage founders and operators. Bring a deck or just bring yourself — drinks on the house for the first hour.'],
        ['title' => 'Hands-On Ceramics Workshop', 'type' => 'workshop', 'city' => [45.5152, -122.6784], 'in_days' => 8, 'venue' => 'Clay Studio PDX', 'price' => 65, 'desc' => 'Throw your first bowl on the wheel under the guidance of resident potters. All materials and firing included; leave with a piece you made.'],
        ['title' => 'Harvest Food & Wine Festival', 'type' => 'festival', 'city' => [38.9072, -77.0369], 'in_days' => 20, 'venue' => 'The Wharf', 'price' => 35, 'desc' => 'Sample dishes from forty local kitchens, pair them with regional wines, and catch live acoustic sets along the waterfront all afternoon.'],
        ['title' => 'City Marathon · Spring Classic', 'type' => 'sports', 'city' => [41.8781, -87.6298], 'in_days' => 30, 'venue' => 'Grant Park', 'price' => 80, 'desc' => 'Run the flat, fast course through the heart of the city. Chip timing, cheer zones every mile, and a finish-line festival for every runner.'],
        ['title' => 'AI & Coffee · Investor Mixer', 'type' => 'networking', 'city' => [42.3601, -71.0589], 'in_days' => 6, 'venue' => 'CIC Boston', 'price' => 15, 'desc' => 'Morning networking for AI builders and angel investors. Curated introductions, lightning intros, and very good espresso.'],
        ['title' => 'Modern Masters · Photography Expo', 'type' => 'exhibition', 'city' => [51.5074, -0.1278], 'in_days' => 14, 'venue' => 'Somerset House', 'price' => 22, 'desc' => 'A landmark exhibition tracing a century of photography, from early portraiture to contemporary digital art, across six themed galleries.'],
        ['title' => 'Midnight Jazz · Quartet Live', 'type' => 'concert', 'city' => [48.8566, 2.3522], 'in_days' => 4, 'venue' => 'Le Caveau de la Huchette', 'price' => 38, 'desc' => 'Intimate late-night set from an acclaimed quartet in a historic Left Bank cellar. Limited seating, unforgettable acoustics.'],
        ['title' => 'Climate Tech Summit', 'type' => 'conference', 'city' => [52.5200, 13.4050], 'in_days' => 25, 'venue' => 'Station Berlin', 'price' => 180, 'desc' => 'Founders, scientists, and policymakers convene to tackle decarbonisation at scale. Keynotes, demos, and a startup pitch arena.'],
        ['title' => 'Design Systems Meetup', 'type' => 'meetup', 'city' => [52.3676, 4.9041], 'in_days' => 9, 'venue' => 'TQ Amsterdam', 'price' => 0, 'desc' => 'Practitioners share how they scale component libraries and tokens across large teams. Talks, Q&A, and stroopwafels.'],
        ['title' => 'Barista Latte Art Workshop', 'type' => 'workshop', 'city' => [45.4642, 9.1900], 'in_days' => 11, 'venue' => 'Officina Caffè', 'price' => 40, 'desc' => 'Learn milk texturing and pour technique from a champion barista. Small group, hands-on, plenty of cups to practice on.'],
        ['title' => 'Lakeside Summer Festival', 'type' => 'festival', 'city' => [47.3769, 8.5417], 'in_days' => 40, 'venue' => 'Zürichhorn Park', 'price' => 25, 'desc' => 'A weekend of music, food trucks, and lakeside fireworks. Family-friendly by day, headline DJ sets after dark.'],
        ['title' => 'Derby Night · Football Classic', 'type' => 'sports', 'city' => [53.4808, -2.2426], 'in_days' => 16, 'venue' => 'Etihad Stadium', 'price' => 60, 'desc' => 'The city rivalry that stops the calendar. Roaring terraces, full house, and ninety minutes you will not want to miss.'],
        ['title' => 'Founders & VCs · Evening Reception', 'type' => 'networking', 'city' => [1.3521, 103.8198], 'in_days' => 7, 'venue' => 'CapitaSpring', 'price' => 20, 'desc' => 'Rooftop reception connecting Southeast Asian founders with regional investors. Skyline views and warm introductions.'],
        ['title' => 'Future Mobility Expo', 'type' => 'exhibition', 'city' => [35.6762, 139.6503], 'in_days' => 22, 'venue' => 'Tokyo Big Sight', 'price' => 18, 'desc' => 'Walk among the next decade of transport: EV concepts, eVTOL prototypes, and autonomy demos from leading manufacturers.'],
        ['title' => 'Stadium Lights · Arena Tour', 'type' => 'concert', 'city' => [-33.8688, 151.2093], 'in_days' => 18, 'venue' => 'Qudos Bank Arena', 'price' => 75, 'desc' => 'The world tour lands down under for one explosive night of stadium-sized production and the hits you know by heart.'],
        ['title' => 'Product Leaders Conference', 'type' => 'conference', 'city' => [43.6532, -79.3832], 'in_days' => 28, 'venue' => 'Evergreen Brick Works', 'price' => 220, 'desc' => 'A day for senior PMs on strategy, discovery, and building teams that ship. Curated talks and small-group roundtables.'],
        ['title' => 'Taco & Mezcal Night Market', 'type' => 'festival', 'city' => [19.4326, -99.1332], 'in_days' => 10, 'venue' => 'Mercado Roma', 'price' => 12, 'desc' => 'A roving night market celebrating street food and small-batch mezcal, with live cumbia and a rooftop dance floor.'],
        ['title' => 'Desert Code · Developer Meetup', 'type' => 'meetup', 'city' => [25.2048, 55.2708], 'in_days' => 13, 'venue' => 'AstroLabs Dubai', 'price' => 0, 'desc' => 'Monthly gathering for the regional developer community. Lightning talks, demos, and great conversations over dinner.'],
    ];

    public function run(): void
    {
        Event::query()->where('payload->featured', true)->delete();

        $useReal = Storage::exists('events/real/concert-1.jpg');

        foreach (self::EVENTS as $i => $data) {
            $startsAt = Carbon::now()->addDays($data['in_days'])->setTime(19, 0);
            [$lat, $lng] = $data['city'];

            $event = Event::create([
                'user_id' => 1,
                'type' => $data['type'],
                'status' => EventStatus::Published->value,
                'created_time' => $startsAt->timestamp,
                'latitude' => $lat,
                'longitude' => $lng,
                'payload' => [
                    'name' => $data['title'],
                    'category' => $data['type'],
                    'description' => $data['desc'],
                    'venue' => ['name' => $data['venue'], 'capacity' => 2000],
                    'location' => ['lat' => $lat, 'lng' => $lng],
                    'schedule' => ['starts_at' => $startsAt->timestamp, 'ends_at' => $startsAt->copy()->addHours(3)->timestamp],
                    'pricing' => ['currency' => 'USD', 'min_price' => $data['price']],
                    'featured' => true,
                ],
            ]);

            $this->attachImages($event, $data['type'], $useReal);

            // A few confirmed attendees each for a lived-in feel.
            foreach (range(1, mt_rand(2, 9)) as $j) {
                Attendee::create([
                    'event_id' => $event->id,
                    'name' => "Guest {$i}-{$j}",
                    'email' => "guest{$i}_{$j}@example.test",
                    'status' => AttendeeStatus::Going->value,
                    'confirmed_at' => now(),
                ]);
            }
        }

        $this->command->info('Seeded '.count(self::EVENTS).' featured showcase events.');
    }

    private function attachImages(Event $event, string $type, bool $useReal): void
    {
        $ext = $useReal ? 'jpg' : 'svg';
        $dir = $useReal ? 'events/real' : 'events';

        $event->images()->create(['path' => "{$dir}/{$type}-1.{$ext}", 'is_primary' => true, 'sort_order' => 0]);
        $event->images()->create(['path' => "{$dir}/{$type}-2.{$ext}", 'is_primary' => false, 'sort_order' => 1]);
        $event->images()->create(['path' => "{$dir}/{$type}-3.{$ext}", 'is_primary' => false, 'sort_order' => 2]);
    }
}
