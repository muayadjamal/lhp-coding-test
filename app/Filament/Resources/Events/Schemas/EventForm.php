<?php

namespace App\Filament\Resources\Events\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Details')
                ->columns(2)
                ->schema([
                    TextInput::make('payload.name')
                        ->label('Title')
                        ->required()
                        ->columnSpanFull(),
                    Textarea::make('payload.description')
                        ->label('Description')
                        ->rows(3)
                        ->columnSpanFull(),
                    Select::make('type')
                        ->options(array_combine(
                            ['concert', 'conference', 'meetup', 'workshop', 'festival', 'sports', 'networking', 'exhibition'],
                            ['Concert', 'Conference', 'Meetup', 'Workshop', 'Festival', 'Sports', 'Networking', 'Exhibition'],
                        ))
                        ->required(),
                    Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'published' => 'Published',
                            'sold_out' => 'Sold out',
                            'cancelled' => 'Cancelled',
                        ])
                        ->required(),
                ]),

            Section::make('Schedule & location')
                ->columns(2)
                ->schema([
                    DateTimePicker::make('starts_at')
                        ->label('Starts at (UTC)')
                        ->seconds(false)
                        ->helperText('Stored as a UTC timestamp; shown in the venue timezone on the site.'),
                    TextInput::make('payload.venue.name')->label('Venue'),
                    TextInput::make('latitude')->numeric()->step('any')->required(),
                    TextInput::make('longitude')->numeric()->step('any')->required(),
                ]),

            Section::make('Pricing')
                ->columns(2)
                ->schema([
                    TextInput::make('payload.pricing.min_price')
                        ->label('From price')
                        ->numeric()
                        ->prefix('$'),
                ]),
        ]);
    }
}
