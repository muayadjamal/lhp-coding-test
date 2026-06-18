<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum EventType: string implements HasLabel
{
    case Concert = 'concert';
    case Conference = 'conference';
    case Meetup = 'meetup';
    case Workshop = 'workshop';
    case Festival = 'festival';
    case Sports = 'sports';
    case Networking = 'networking';
    case Exhibition = 'exhibition';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Bare case values, e.g. for the public filter endpoint.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }
}
