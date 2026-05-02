<?php

namespace App\Filament\Resources;

use App\Models\Appointment;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Client;
use App\Filament\Resources\AppointmentResource\Pages;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Carbon\Carbon;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar';
    protected static string|\UnitEnum|null $navigationGroup = 'Bookings';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([

            Forms\Components\Select::make('client_id')
                ->label('Client')
                ->relationship('client', 'name')
                ->searchable()
                ->preload()
                ->createOptionForm([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('phone')->required(),
                    Forms\Components\TextInput::make('email')->email(),
                ])
                ->required(),

            Forms\Components\Select::make('service_id')
                ->label('Service')
                ->relationship('service', 'name')
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function (Set $set) {
                    $set('staff_id', null);
                    $set('start_time', null);
                })
                ->required(),

            Forms\Components\DatePicker::make('appt_date')
                ->label('Date')
                ->native(false)
                ->minDate(now())
                ->live()
                ->afterStateUpdated(function (Set $set) {
                    $set('staff_id', null);
                    $set('start_time', null);
                })
                ->required(),

            Forms\Components\Select::make('staff_id')
                ->label('Therapist / Stylist')
                ->options(function (Get $get) {
                    $serviceId = $get('service_id');
                    $date = $get('appt_date');
                    if (!$serviceId || !$date) return [];

                    $dayOfWeek = Carbon::parse($date)->dayOfWeek;

                    return Staff::whereHas('services', fn($q) => $q->where('service_id', $serviceId))
                        ->whereHas('schedules', fn($q) => $q
                            ->where('day_of_week', $dayOfWeek)
                            ->where('is_day_off', false)
                        )
                        ->where('is_active', true)
                        ->pluck('name', 'id');
                })
                ->live()
                ->afterStateUpdated(fn(Set $set) => $set('start_time', null))
                ->required(),

            Forms\Components\Select::make('start_time')
                ->label('Time slot')
                ->options(function (Get $get) {
                    $staffId  = $get('staff_id');
                    $date     = $get('appt_date');
                    $serviceId = $get('service_id');
                    if (!$staffId || !$date || !$serviceId) return [];

                    return static::getAvailableSlots($staffId, $date, $serviceId);
                })
                ->required(),

            Forms\Components\Select::make('status')
                ->options([
                    'pending'     => 'Pending',
                    'confirmed'   => 'Confirmed',
                    'in_progress' => 'In progress',
                    'completed'   => 'Completed',
                    'cancelled'   => 'Cancelled',
                    'no_show'     => 'No show',
                ])
                ->default('confirmed')
                ->required(),

            Forms\Components\Textarea::make('notes')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    // Core availability logic
    public static function getAvailableSlots(int $staffId, string $date, int $serviceId): array
    {
        $carbon = Carbon::parse($date);
        $dayOfWeek = $carbon->dayOfWeek;

        $schedule = \App\Models\StaffSchedule::where('staff_id', $staffId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_day_off', false)
            ->first();

        if (!$schedule) return [];

        $service      = Service::find($serviceId);
        $duration     = $service->duration_minutes;
        $slotInterval = 30; // minutes between slot options

        $workStart = Carbon::parse($date . ' ' . $schedule->start_time);
        $workEnd   = Carbon::parse($date . ' ' . $schedule->end_time);

        // Fetch booked slots for this staff on this date
        $booked = Appointment::where('staff_id', $staffId)
            ->where('appt_date', $date)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->get(['start_time', 'end_time'])
            ->map(fn($a) => [
                'start' => Carbon::parse($date . ' ' . $a->start_time),
                'end'   => Carbon::parse($date . ' ' . $a->end_time),
            ]);

        $slots = [];
        $cursor = $workStart->copy();

        while ($cursor->copy()->addMinutes($duration)->lte($workEnd)) {
            $slotEnd = $cursor->copy()->addMinutes($duration);

            $overlaps = $booked->first(fn($b) =>
                $cursor->lt($b['end']) && $slotEnd->gt($b['start'])
            );

            if (!$overlaps) {
                $slots[$cursor->format('H:i')] = $cursor->format('h:i A');
            }

            $cursor->addMinutes($slotInterval);
        }

        return $slots;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('appt_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('start_time'),
                Tables\Columns\TextColumn::make('client.name')->searchable(),
                Tables\Columns\TextColumn::make('staff.name')->searchable(),
                Tables\Columns\TextColumn::make('service.name'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning'  => 'pending',
                        'success'  => 'completed',
                        'primary'  => 'confirmed',
                        'danger'   => ['cancelled', 'no_show'],
                        'secondary' => 'in_progress',
                    ]),
                Tables\Columns\TextColumn::make('total_price')
                    ->money('USD')->sortable(),
            ])
            ->defaultSort('appt_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status'),
                Tables\Filters\SelectFilter::make('staff_id')
                    ->relationship('staff', 'name'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit'   => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}
