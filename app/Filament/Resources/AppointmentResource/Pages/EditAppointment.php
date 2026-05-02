<?php

namespace App\Filament\Resources\AppointmentResource\Pages;

use App\Filament\Resources\AppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;

class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        AppointmentResource::ensureAppointmentIsValid($data, $this->record->id);

        $data['start_time'] = Carbon::parse($data['start_time'])->format('H:i:s');
        $data['end_time'] = Carbon::parse($data['end_time'])->format('H:i:s');

        return $data;
    }
}
