<?php

namespace App\Filament\Resources\AppointmentResource\Pages;

use App\Filament\Resources\AppointmentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        AppointmentResource::ensureAppointmentIsValid($data);

        $data['start_time'] = Carbon::parse($data['start_time'])->format('H:i:s');
        $data['end_time'] = Carbon::parse($data['end_time'])->format('H:i:s');

        return $data;
    }
}
