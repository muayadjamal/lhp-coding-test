<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The filter option lists (statuses, types, countries, cities) for the public
 * filter UIs. A flat options payload — don't wrap it in "data".
 */
class FilterOptionsResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
