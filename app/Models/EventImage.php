<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class EventImage extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    protected $appends = ['url'];

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Public URL for the locally-stored image. The default disk resolves to a
     * host-relative `/storage/...` path (see config/filesystems.php), so images
     * work regardless of the host/port the app is served on.
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }
}
