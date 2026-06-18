<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Generates a small pool of local placeholder images (SVG) on the `public`
 * disk, one set per event category plus a few generic ones. Events reference
 * these by path — the files are served locally, never hotlinked.
 */
class MakePlaceholderImages extends Command
{
    protected $signature = 'events:make-placeholders';

    protected $description = 'Generate local placeholder images for events';

    /** Category => [from, to] gradient colours. */
    private const PALETTES = [
        'concert' => ['#7c3aed', '#ec4899'],
        'conference' => ['#2563eb', '#4f46e5'],
        'meetup' => ['#0d9488', '#22c55e'],
        'workshop' => ['#d97706', '#f97316'],
        'festival' => ['#c026d3', '#f43f5e'],
        'sports' => ['#16a34a', '#84cc16'],
        'networking' => ['#0891b2', '#0ea5e9'],
        'exhibition' => ['#475569', '#8b5cf6'],
        'generic' => ['#334155', '#0ea5e9'],
    ];

    private const VARIANTS = 3;

    public function handle(): int
    {
        $count = 0;

        foreach (self::PALETTES as $category => [$from, $to]) {
            for ($i = 1; $i <= self::VARIANTS; $i++) {
                Storage::put("events/{$category}-{$i}.svg", $this->svg($category, $i, $from, $to));
                $count++;
            }
        }

        $this->info("Generated {$count} placeholder images on the public disk.");

        return self::SUCCESS;
    }

    private function svg(string $category, int $variant, string $from, string $to): string
    {
        $label = ucfirst($category);
        // Rotate the gradient and circle position per variant so the multiple
        // images attached to one event look distinct.
        $angle = ($variant - 1) * 35;
        $cx = 120 + ($variant * 180);
        $cy = 90 + ($variant * 40);

        return <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" width="800" height="500" viewBox="0 0 800 500" role="img" aria-label="{$label} placeholder">
              <defs>
                <linearGradient id="g" gradientTransform="rotate({$angle})">
                  <stop offset="0%" stop-color="{$from}"/>
                  <stop offset="100%" stop-color="{$to}"/>
                </linearGradient>
              </defs>
              <rect width="800" height="500" fill="url(#g)"/>
              <circle cx="{$cx}" cy="{$cy}" r="220" fill="#ffffff" opacity="0.08"/>
              <circle cx="{$cx}" cy="{$cy}" r="140" fill="#ffffff" opacity="0.08"/>
              <text x="48" y="430" font-family="system-ui, sans-serif" font-size="56" font-weight="700" fill="#ffffff">{$label}</text>
              <text x="50" y="470" font-family="system-ui, sans-serif" font-size="22" fill="#ffffff" opacity="0.8">Event Visuals · {$variant}</text>
            </svg>
            SVG;
    }
}
