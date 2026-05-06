<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AppointmentCalendarWidget;
use App\Filament\Widgets\SelectedDateAppointmentsWidget;
use App\Filament\Widgets\StaffScheduleMatrixWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            AppointmentCalendarWidget::class,
            SelectedDateAppointmentsWidget::class,
            StaffScheduleMatrixWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return [
            'default' => 1,
            'xl' => 3,
        ];
    }
}
