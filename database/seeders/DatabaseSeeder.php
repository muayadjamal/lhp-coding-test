<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Admin login for the Filament panel at /admin. `is_admin` is guarded
        // (not fillable), so set it explicitly via forceFill.
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@admin.com'],
            ['name' => 'Admin', 'password' => Hash::make('123123'), 'email_verified_at' => now()],
        );
        $admin->forceFill(['is_admin' => true])->save();

        // Bulk dataset. Defaults to 1,250,000 events; override with SEED_ROWS,
        // e.g. SEED_ROWS=5000 php artisan db:seed
        $this->call(EventSeeder::class);

        // Local images + attendees + reminder demo events, then the curated
        // featured showcase events.
        $this->call(EventMediaSeeder::class);
        $this->call(ShowcaseSeeder::class);
    }
}
