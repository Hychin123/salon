<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Carbon\Carbon;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class AppointmentCalendarWidget extends FullCalendarWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = [
        'default' => 1,
        'xl' => 2,
    ];

    public static function canView(): bool
    {
        return auth()->user()?->can('view_any_appointment') ?? false;
    }

    public function onDateSelect(string $start, ?string $end, bool $allDay, ?array $view, ?array $resource): void
    {
        $this->dispatch('dashboard-date-selected', date: Carbon::parse($start)->toDateString());
    }

    public function onEventClick(array $event): void
    {
        $eventStart = data_get($event, 'start');

        if ($eventStart) {
            $this->dispatch('dashboard-date-selected', date: Carbon::parse($eventStart)->toDateString());
        }
    }

    public function fetchEvents(array $fetchInfo): array
    {
        $query = Appointment::with(['client', 'staff', 'service'])
            ->whereBetween('appt_date', [
                Carbon::parse($fetchInfo['start'])->toDateString(),
                Carbon::parse($fetchInfo['end'])->toDateString(),
            ])
            ->whereNotIn('status', ['cancelled']);

        $user = auth()->user();
        if ($user?->hasAnyRole(['therapist', 'stylist'])) {
            $query->whereHas('staff', fn ($q) => $q->where('user_id', $user->id));
        }

        return $query->get()
            ->map(function ($appt) {
                $baseDate = Carbon::parse($appt->appt_date);

                return EventData::make()
                    ->id($appt->id)
                    ->title(($appt->client?->name ?? 'Client') . ' — ' . ($appt->service?->name ?? 'Service'))
                    ->start($baseDate->copy()->setTimeFromTimeString((string) $appt->start_time))
                    ->end($baseDate->copy()->setTimeFromTimeString((string) $appt->end_time))
                    ->backgroundColor(match ($appt->status) {
                        'confirmed' => '#1D9E75',
                        'pending' => '#EF9F27',
                        'in_progress' => '#534AB7',
                        'completed' => '#639922',
                        default => '#888780',
                    })
                    ->extendedProps(['staff' => $appt->staff?->name ?? 'Staff']);
            })
            ->toArray();
    }

    public function config(): array
    {
        return [
            'initialView' => 'timeGridWeek',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay',
            ],
            'slotMinTime' => '08:00:00',
            'slotMaxTime' => '21:00:00',
            'slotDuration' => '00:30:00',
            'allDaySlot' => false,
            'nowIndicator' => true,
            'businessHours' => [
                'daysOfWeek' => [1, 2, 3, 4, 5, 6],
                'startTime' => '09:00',
                'endTime' => '19:00',
            ],
        ];
    }
}
