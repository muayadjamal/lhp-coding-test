<?php

namespace App\Filament\Resources\Attendees\Tables;

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
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => $state === 'going' ? 'success' : 'gray'),
                TextColumn::make('confirmed_at')->dateTime()->label('Confirmed')->placeholder('—')->toggleable(),
                TextColumn::make('reminder_3d_sent_at')->dateTime()->label('3-day')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reminder_24h_sent_at')->dateTime()->label('24h')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime()->label('Registered')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(['going' => 'Going', 'interested' => 'Interested']),
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
