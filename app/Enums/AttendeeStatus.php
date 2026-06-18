<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AttendeeStatus: string implements HasColor, HasLabel
{
    case Going = 'going';
    case Interested = 'interested';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string
    {
        return $this === self::Going ? 'success' : 'gray';
    }

    /**
     * Bare case values, e.g. for factories and validation.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }
}
