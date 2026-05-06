<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffResource\Pages;
use App\Filament\Resources\StaffResource\RelationManagers\SchedulesRelationManager;
use App\Models\Staff;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Staff';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Staff Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->nullable()
                            ->maxLength(20),
                        Forms\Components\Select::make('role')
                            ->label('Role')
                            ->options(fn () => Role::where('name', '!=', 'super_admin')->pluck('name', 'name'))
                            ->required(),
                        Forms\Components\TextInput::make('commission_rate')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->label('Commission Rate (%)'),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ]),
                Section::make('Login Account')
                    ->schema([
                        Forms\Components\TextInput::make('user_email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                table: User::class,
                                column: 'email',
                                ignorable: fn (?Staff $record) => $record?->user,
                            ),
                        Forms\Components\TextInput::make('user_password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->confirmed()
                            ->minLength(8)
                            ->required(fn (?Staff $record) => blank($record?->user))
                            ->dehydrated(fn (?string $state) => filled($state)),
                        Forms\Components\TextInput::make('user_password_confirmation')
                            ->label('Confirm password')
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->required(fn (?Staff $record) => blank($record?->user)),
                    ]),
                Section::make('Services')
                    ->schema([
                        Forms\Components\CheckboxList::make('services')
                            ->relationship('services', 'name'),
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
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'manager' => 'success',
                        'receptionist' => 'info',
                        'therapist' => 'warning',
                        'stylist' => 'warning',
                        'super_admin' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Str::title(str_replace('_', ' ', $state)))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('commission_rate')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->label('Commission %'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaff::route('/'),
            'create' => Pages\CreateStaff::route('/create'),
            'edit' => Pages\EditStaff::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            SchedulesRelationManager::class,
        ];
    }

    public static function getNavigationGroup(): string |\UnitEnum | null
    {
        return 'Salon Management';
    }
}
