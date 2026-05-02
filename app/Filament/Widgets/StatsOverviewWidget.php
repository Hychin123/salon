<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $todayCount = Appointment::whereDate('appt_date', $today)->whereNotIn('status', ['cancelled'])->count();
        $yesterdayCount = Appointment::whereDate('appt_date', $yesterday)->whereNotIn('status', ['cancelled'])->count();
        $revenue = Payment::whereDate('paid_at', $today)->sum('amount');
        $available = Appointment::whereDate('appt_date', $today)->where('status', 'pending')->count();
        $newClients = Client::where('created_at', '>=', now()->startOfWeek())->count();

        return [
            Stat::make("Today's bookings", $todayCount)
                ->description($todayCount >= $yesterdayCount ? '+' . ($todayCount - $yesterdayCount) . ' vs yesterday' : ($todayCount - $yesterdayCount) . ' vs yesterday')
                ->color($todayCount >= $yesterdayCount ? 'success' : 'danger'),

            Stat::make('Revenue today', '$' . number_format((float) $revenue, 2))
                ->description(Appointment::whereDate('appt_date', $today)->where('status', 'completed')->count() . ' completed')
                ->color('success'),

            Stat::make('Pending slots', $available)
                ->description('Awaiting confirmation')
                ->color('warning'),

            Stat::make('New clients', $newClients)
                ->description('this week')
                ->color('info'),
        ];
    }
}
