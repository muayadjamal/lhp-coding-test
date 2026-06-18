<?php

namespace App\Filament\Resources\Events\Tables;

use App\Enums\EventStatus;
use App\Enums\EventType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('images')->withCount('attendees'))
            ->columns([
                ImageColumn::make('primary_image')
                    ->label('')
                    ->getStateUsing(fn ($record) => $record->images->first()?->path)
                    ->height(44)
                    ->width(64)
                    ->extraImgAttributes(['class' => 'rounded-md object-cover']),
                TextColumn::make('title')
                    ->label('Title')
                    ->getStateUsing(fn ($record) => $record->title())
                    ->description(fn ($record) => $record->location()['label'] ?? null)
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereRaw('json_extract(payload, \'$.name\') like ?', ["%{$search}%"]))
                    ->wrap(),
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                // Label + colour come from the EventStatus enum (HasLabel/HasColor).
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('Starts')
                    ->getStateUsing(fn ($record) => $record->startsAt()?->format('M j, Y · H:i'))
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('created_time', $direction === 'desc' ? 'desc' : 'asc')),
                TextColumn::make('attendees_count')
                    ->label('Going')
                    ->badge()
                    ->color('info')
                    ->sortable(),
            ])
            ->defaultSort('created_time', 'desc')
            ->filters([
                SelectFilter::make('type')->options(EventType::class),
                SelectFilter::make('status')->options(EventStatus::class),
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
