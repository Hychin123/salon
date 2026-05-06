<?php

namespace App\Filament\Widgets;

use App\Models\Staff;
use Filament\Widgets\Widget;

class StaffScheduleMatrixWidget extends Widget
{
    protected string $view = 'filament.widgets.staff-schedule-matrix-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'manager']) ?? false;
    }

    protected function getViewData(): array
    {
        $days = [
            0 => 'Sun',
            1 => 'Mon',
            2 => 'Tue',
            3 => 'Wed',
            4 => 'Thu',
            5 => 'Fri',
            6 => 'Sat',
        ];

        $staffRows = Staff::query()
            ->where('is_active', true)
            ->with(['schedules' => fn ($query) => $query->orderBy('day_of_week')])
            ->orderBy('name')
            ->get()
            ->map(function (Staff $staff) use ($days): array {
                $scheduleByDay = $staff->schedules->keyBy('day_of_week');

                $cells = collect($days)->map(function (string $label, int $day) use ($scheduleByDay): array {
                    $schedule = $scheduleByDay->get($day);

                    if (! $schedule) {
                        return [
                            'text' => 'Off',
                            'type' => 'off',
                        ];
                    }

                    if ($schedule->is_day_off) {
                        return [
                            'text' => 'Off',
                            'type' => 'off',
                        ];
                    }

                    if (! $schedule->start_time || ! $schedule->end_time) {
                        return [
                            'text' => 'Full',
                            'type' => 'full',
                        ];
                    }

                    return [
                        'text' => substr($schedule->start_time, 0, 5) . '-' . substr($schedule->end_time, 0, 5),
                        'type' => 'working',
                    ];
                })->all();

                return [
                    'name' => $staff->name,
                    'cells' => $cells,
                ];
            })
            ->all();

        return [
            'days' => array_values($days),
            'staffRows' => $staffRows,
        ];
    }
}
