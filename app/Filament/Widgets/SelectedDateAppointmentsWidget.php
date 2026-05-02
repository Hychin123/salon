<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Livewire\Attributes\On;
use Filament\Widgets\Widget;

class SelectedDateAppointmentsWidget extends Widget
{
    protected string $view = 'filament.widgets.selected-date-appointments-widget';

    protected int | string | array $columnSpan = [
        'default' => 1,
        'xl' => 1,
    ];

    protected static ?int $sort = 3;

    public string $selectedDate;

    public function mount(): void
    {
        $this->selectedDate = now()->toDateString();
    }

    #[On('dashboard-date-selected')]
    public function setSelectedDate(string $date): void
    {
        $this->selectedDate = $date;
    }

    protected function getViewData(): array
    {
        $appointments = Appointment::query()
            ->with(['client', 'service', 'staff'])
            ->whereDate('appt_date', $this->selectedDate)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('start_time')
            ->get();

        return [
            'selectedDateLabel' => \Carbon\Carbon::parse($this->selectedDate)->format('M d, Y'),
            'appointments' => $appointments,
        ];
    }
}
