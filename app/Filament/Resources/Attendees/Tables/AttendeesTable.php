<?php

namespace App\Filament\Resources\Attendees\Tables;

use App\Enums\AttendeeStatus;
use App\Models\Event;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('event'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->copyable(),
                TextColumn::make('event')
                    ->label('Event')
                    ->getStateUsing(fn ($record) => $record->event?->title())
                    ->wrap(),
                // Label + colour come from the AttendeeStatus enum.
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('confirmed_at')->dateTime()->label('Confirmed')->placeholder('—')->toggleable(),
                TextColumn::make('reminder_3d_sent_at')->dateTime()->label('3-day')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reminder_24h_sent_at')->dateTime()->label('24h')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime()->label('Registered')->sortable(),
            ])
            ->filters([
                SelectFilter::make('event_id')
                    ->label('Event')
                    ->searchable()
                    // Search-driven, not preloaded: the events table is huge, so
                    // query titles (in the JSON payload) on demand and cap the
                    // result set. Only events with registrations are reachable.
                    ->getSearchResultsUsing(fn (string $search): array => Event::query()
                        ->whereHas('attendees')
                        ->whereRaw('json_extract(payload, \'$.name\') like ?', ["%{$search}%"])
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Event $e) => [$e->id => $e->title()])
                        ->all())
                    ->getOptionLabelUsing(fn (string $value): ?string => Event::query()->find($value)?->title()),
                SelectFilter::make('status')->options(AttendeeStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
