<?php

namespace App\Filament\Resources\StaffResource\RelationManagers;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';

    protected static ?string $title = 'Weekly Schedule';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('day_of_week')
                    ->label('Day')
                    ->options([
                        0 => 'Sunday',
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday',
                    ])
                    ->required(),
                Forms\Components\Toggle::make('is_day_off')
                    ->label('Day Off')
                    ->live(),
                Forms\Components\TimePicker::make('start_time')
                    ->seconds(false)
                    ->hidden(fn (callable $get): bool => (bool) $get('is_day_off')),
                Forms\Components\TimePicker::make('end_time')
                    ->seconds(false)
                    ->hidden(fn (callable $get): bool => (bool) $get('is_day_off')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('day_of_week')
            ->modifyQueryUsing(fn ($query) => $query->orderBy('day_of_week'))
            ->columns([
                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('Day')
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        0 => 'Sun',
                        1 => 'Mon',
                        2 => 'Tue',
                        3 => 'Wed',
                        4 => 'Thu',
                        5 => 'Fri',
                        6 => 'Sat',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Schedule')
                    ->state(function ($record): string {
                        if ($record->is_day_off) {
                            return 'Off';
                        }

                        if (! $record->start_time || ! $record->end_time) {
                            return 'Full';
                        }

                        return substr($record->start_time, 0, 5) . '-' . substr($record->end_time, 0, 5);
                    })
                    ->badge()
                    ->color(function ($record): string {
                        if ($record->is_day_off) {
                            return 'gray';
                        }

                        if (! $record->start_time || ! $record->end_time) {
                            return 'danger';
                        }

                        return 'success';
                    }),
            ])
            ->headerActions([
                Actions\CreateAction::make(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
