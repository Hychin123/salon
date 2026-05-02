<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers\AppointmentsRelationManager;
use App\Models\Client;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Clients';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Client Information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->unique(ignorable: fn ($record) => $record)
                            ->required()
                            ->maxLength(20)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->unique(ignorable: fn ($record) => $record)
                            ->nullable()
                            ->maxLength(255)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('loyalty_points')
                            ->numeric()
                            ->default(0)
                            ->columnSpan(1),
                    ]),
                Section::make('Care Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->placeholder('General notes')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('allergy_notes')
                            ->placeholder('Allergy notes')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('health_notes')
                            ->placeholder('Health notes')
                            ->columnSpanFull(),
                    ]),
                Section::make('Preferences')
                    ->schema([
                        Forms\Components\CheckboxList::make('preferredServices')
                            ->relationship('preferredServices', 'name')
                            ->label('Preferred Services')
                            ->columns(3)
                            ->searchable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('loyalty_points')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('preferredServices.name')
                    ->label('Preferred Services')
                    ->badge()
                    ->separator(', '),
                Tables\Columns\TextColumn::make('appointments_count')
                    ->label('Visit Count')
                    ->sortable(),
                Tables\Columns\TextColumn::make('appointments_max_appt_date')
                    ->label('Last Visit')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            AppointmentsRelationManager::class,
        ];
    }

    public static function getNavigationGroup(): string |\UnitEnum | null
    {
        return 'CRM';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('appointments')
            ->withMax('appointments', 'appt_date');
    }
}
