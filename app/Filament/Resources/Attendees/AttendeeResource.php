<?php

namespace App\Filament\Resources\Attendees;

use App\Filament\Resources\Attendees\Pages\ListAttendees;
use App\Filament\Resources\Attendees\Schemas\AttendeeForm;
use App\Filament\Resources\Attendees\Tables\AttendeesTable;
use App\Models\Attendee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AttendeeResource extends Resource
{
    protected static ?string $model = Attendee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    /** @return array<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    /**
     * Eager-load the event so result details don't trigger a query per row.
     *
     * @return Builder<Attendee>
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return Attendee::query()->with('event');
    }

    /**
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        if (! $record instanceof Attendee) {
            return [];
        }

        return [
            'Email' => $record->email,
            'Event' => $record->event?->title() ?? '—',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return AttendeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendeesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttendees::route('/'),
        ];
    }
}
