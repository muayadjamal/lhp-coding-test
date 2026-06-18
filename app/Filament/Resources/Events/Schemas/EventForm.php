<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Models\Event;
use Closure;
use Dotswan\MapPicker\Fields\Map;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;

class EventForm
{
    private const TYPES = [
        'concert' => 'Concert',
        'conference' => 'Conference',
        'meetup' => 'Meetup',
        'workshop' => 'Workshop',
        'festival' => 'Festival',
        'sports' => 'Sports',
        'networking' => 'Networking',
        'exhibition' => 'Exhibition',
    ];

    private const STATUSES = [
        'draft' => 'Draft',
        'published' => 'Published',
        'sold_out' => 'Sold out',
        'cancelled' => 'Cancelled',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Event details')
                ->description('What the event is and how it is listed publicly.')
                ->icon(Heroicon::OutlinedInformationCircle)
                ->columns(2)
                ->schema([
                    TextInput::make('payload.name')
                        ->label('Title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Textarea::make('payload.description')
                        ->label('Description')
                        ->rows(4)
                        ->columnSpanFull(),
                    Select::make('type')
                        ->options(self::TYPES)
                        ->native(false)
                        ->required(),
                    Select::make('status')
                        ->options(self::STATUSES)
                        ->native(false)
                        ->required(),
                    Toggle::make('payload.featured')
                        ->label('Featured')
                        ->helperText('Featured events float to the top of the listings.')
                        ->inline(false),
                ]),

            Section::make('Schedule')
                ->description('When the event begins. Stored in UTC, shown in the venue timezone on the site.')
                ->icon(Heroicon::OutlinedClock)
                ->columns(2)
                ->schema([
                    DateTimePicker::make('starts_at')
                        ->label('Starts at (UTC)')
                        ->seconds(false)
                        ->native(false),
                ]),

            Section::make('Location')
                ->description('Click or drag the marker to place the event — the coordinates update automatically.')
                ->icon(Heroicon::OutlinedMapPin)
                ->columns(2)
                ->schema([
                    TextInput::make('payload.venue.name')
                        ->label('Venue')
                        ->columnSpanFull(),

                    Map::make('location')
                        ->label('Pick on map')
                        ->columnSpanFull()
                        ->defaultLocation(latitude: 40.7128, longitude: -74.0060)
                        ->draggable()
                        ->clickable(true)
                        ->zoom(12)
                        ->minZoom(2)
                        ->maxZoom(19)
                        ->tilesUrl('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png')
                        ->detectRetina()
                        ->showMarker()
                        ->markerColor('#e60023')
                        // Package ships no fullscreen icon asset (404s); keep it off.
                        ->showFullscreenControl(false)
                        ->showZoomControl()
                        ->extraStyles(['min-height: 24rem', 'border-radius: 12px'])
                        // Don't persist — the marker only drives latitude/longitude.
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Set $set, ?Event $record): void {
                            if ($record && $record->latitude !== null && $record->longitude !== null) {
                                $set('location', [
                                    'lat' => (float) $record->latitude,
                                    'lng' => (float) $record->longitude,
                                ]);
                            }
                        })
                        ->afterStateUpdated(function (Set $set, ?array $state): void {
                            if (isset($state['lat'], $state['lng'])) {
                                $set('latitude', round((float) $state['lat'], 7));
                                $set('longitude', round((float) $state['lng'], 7));
                            }
                        }),

                    TextInput::make('latitude')
                        ->numeric()
                        ->step('any')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(self::syncMarker()),
                    TextInput::make('longitude')
                        ->numeric()
                        ->step('any')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(self::syncMarker()),
                ]),

            Section::make('Pricing')
                ->description('Starting ticket price shown on the public listing.')
                ->icon(Heroicon::OutlinedBanknotes)
                ->columns(2)
                ->schema([
                    TextInput::make('payload.pricing.min_price')
                        ->label('From price')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('$'),
                    TextInput::make('payload.pricing.currency')
                        ->label('Currency')
                        ->default('USD')
                        ->maxLength(3),
                ]),
        ]);
    }

    /**
     * When a coordinate is typed by hand, move the map marker to match.
     */
    private static function syncMarker(): Closure
    {
        return function (Set $set, Get $get, Component $livewire): void {
            $lat = $get('latitude');
            $lng = $get('longitude');

            if (is_numeric($lat) && is_numeric($lng)) {
                $set('location', ['lat' => (float) $lat, 'lng' => (float) $lng]);
                $livewire->dispatch('refreshMap');
            }
        };
    }
}
