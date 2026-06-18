<?php

namespace App\Filament\Resources\Events;

use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\Pages\ListEvents;
use App\Filament\Resources\Events\RelationManagers\AttendeesRelationManager;
use App\Filament\Resources\Events\RelationManagers\ImagesRelationManager;
use App\Filament\Resources\Events\Schemas\EventForm;
use App\Filament\Resources\Events\Tables\EventsTable;
use App\Models\Event;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 1;

    /**
     * Title and the other display fields live in the JSON `payload`, so the
     * default attribute search can't see them. Declare the attribute (so the
     * resource opts into global search) and drive the actual matching through
     * a json_extract constraint below.
     *
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['payload.name'];
    }

    /**
     * @param  Builder<Event>  $query
     */
    protected static function applyGlobalSearchAttributeConstraints(Builder $query, string $search): void
    {
        $term = "%{$search}%";

        $query->where(function (Builder $query) use ($term): void {
            $query->whereRaw('json_extract(payload, \'$.name\') like ?', [$term])
                ->orWhere('type', 'like', $term)
                ->orWhere('status', 'like', $term);
        });
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record instanceof Event ? $record->title() : (string) $record->getKey();
    }

    /**
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        if (! $record instanceof Event) {
            return [];
        }

        return [
            'Type' => $record->type->getLabel(),
            'Status' => $record->status->getLabel(),
            'When' => $record->startsAt()?->format('M j, Y') ?? '—',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return EventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ImagesRelationManager::class,
            AttendeesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }
}
