<?php

namespace App\Filament\Resources\Attendees\Schemas;

use App\Enums\AttendeeStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AttendeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('event_id')
                ->label('Event')
                ->relationship('event', 'id')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->title())
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('name')->required(),
            TextInput::make('email')->email()->required(),
            Select::make('status')
                ->options(AttendeeStatus::class)
                ->default(AttendeeStatus::Going->value)
                ->required(),
        ]);
    }
}
