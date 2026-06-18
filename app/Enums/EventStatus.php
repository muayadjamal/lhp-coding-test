<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EventStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Published = 'published';
    case SoldOut = 'sold_out';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::SoldOut => 'Sold out',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Published => 'success',
            self::SoldOut => 'warning',
            self::Cancelled => 'danger',
            self::Draft => 'gray',
        };
    }

    /**
     * Every status as a bare value, e.g. for seeding the full spread.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }

    /**
     * Statuses worth showing on the public pages — drafts stay hidden.
     *
     * @return list<self>
     */
    public static function browsable(): array
    {
        return [self::Published, self::SoldOut, self::Cancelled];
    }

    /**
     * The browsable statuses as bare values, e.g. for `whereIn`.
     *
     * @return list<string>
     */
    public static function browsableValues(): array
    {
        return array_map(fn (self $status) => $status->value, self::browsable());
    }
}
