<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Services\Geocoder;
use App\Support\LocationFilter;
use Carbon\CarbonImmutable;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'type' => EventType::class,
        'status' => EventStatus::class,
    ];

    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<EventImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(EventImage::class)->orderByDesc('is_primary')->orderBy('sort_order');
    }

    /** @return HasMany<Attendee, $this> */
    public function attendees(): HasMany
    {
        return $this->hasMany(Attendee::class);
    }

    // --- Payload accessors -------------------------------------------------
    // Title, description and the rest live inside the JSON `payload` column.

    public function title(): string
    {
        return (string) ($this->payload['name'] ?? 'Untitled event');
    }

    public function description(): string
    {
        return (string) ($this->payload['description'] ?? '');
    }

    public function venueName(): ?string
    {
        return $this->payload['venue']['name'] ?? null;
    }

    public function minPrice(): ?float
    {
        $price = $this->payload['pricing']['min_price'] ?? null;

        return $price === null ? null : (float) $price;
    }

    public function currency(): string
    {
        return (string) ($this->payload['pricing']['currency'] ?? 'USD');
    }

    /**
     * Event start as a UTC instant. The seeded `created_time` (unix seconds)
     * is the canonical start; fall back to the payload schedule if missing.
     */
    public function startsAt(): ?CarbonImmutable
    {
        $ts = $this->created_time ?? ($this->payload['schedule']['starts_at'] ?? null);

        return $ts === null ? null : CarbonImmutable::createFromTimestampUTC((int) $ts);
    }

    public function endsAt(): ?CarbonImmutable
    {
        $ts = $this->payload['schedule']['ends_at'] ?? null;

        return $ts === null ? null : CarbonImmutable::createFromTimestampUTC((int) $ts);
    }

    /**
     * @return array{city: string, country: string, country_name: string, label: string, tz: string}|null
     */
    public function location(): ?array
    {
        return app(Geocoder::class)->resolve($this->latitude, $this->longitude);
    }

    /**
     * Presentation payload shared by both visual pages and the detail view.
     * Times are delivered in UTC ISO-8601 plus the venue's local timezone so
     * the frontend can render either without guessing.
     *
     * @return array<string, mixed>
     */
    public function toDisplayArray(): array
    {
        $location = $this->location();
        $tz = $location['tz'] ?? 'UTC';
        $startsUtc = $this->startsAt();
        $startLocal = $startsUtc?->setTimezone($tz);

        return [
            'id' => $this->id,
            'title' => $this->title(),
            'description' => $this->description(),
            'type' => $this->type->value,
            'status' => $this->status->value,
            'featured' => (bool) ($this->payload['featured'] ?? false),
            'venue' => $this->venueName(),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'location_label' => $location['label'] ?? null,
            'city' => $location['city'] ?? null,
            'country' => $location['country'] ?? null,
            'country_name' => $location['country_name'] ?? null,
            'timezone' => $tz,
            'starts_at_utc' => $startsUtc?->toIso8601String(),
            'starts_at_local' => $startLocal?->format('D, M j, Y · g:i A'),
            'min_price' => $this->minPrice(),
            'currency' => $this->currency(),
            'images' => $this->relationLoaded('images')
                ? $this->images->map(fn (EventImage $i) => $i->url)->all()
                : [],
            'attendees_count' => $this->attendees_count ?? ($this->relationLoaded('attendees') ? $this->attendees->count() : null),
        ];
    }

    // --- Query scopes ------------------------------------------------------

    /**
     * Apply the shared listing/map filters: date range, status, type and
     * location (city or country, expanded to bounding boxes around the
     * seeded city anchors), plus an explicit map bounding box.
     *
     * @param  Builder<Event>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Event>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['from'])) {
            $query->where('created_time', '>=', CarbonImmutable::parse($filters['from'], 'UTC')->startOfDay()->timestamp);
        }

        if (! empty($filters['to'])) {
            $query->where('created_time', '<=', CarbonImmutable::parse($filters['to'], 'UTC')->endOfDay()->timestamp);
        }

        // Explicit bounding box (used by the map viewport).
        if (isset($filters['north'], $filters['south'], $filters['east'], $filters['west'])) {
            $query->whereBetween('latitude', [(float) $filters['south'], (float) $filters['north']])
                ->whereBetween('longitude', [(float) $filters['west'], (float) $filters['east']]);
        }

        // Location filter by city and/or country, expanded to anchor bboxes.
        $boxes = LocationFilter::boxesFor($filters['city'] ?? null, $filters['country'] ?? null);
        if ($boxes !== null) {
            $query->where(function (Builder $q) use ($boxes) {
                foreach ($boxes as $box) {
                    $q->orWhere(function (Builder $inner) use ($box) {
                        $inner->whereBetween('latitude', [$box['south'], $box['north']])
                            ->whereBetween('longitude', [$box['west'], $box['east']]);
                    });
                }
            });
        }

        return $query;
    }
}
