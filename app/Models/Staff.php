<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Staff extends Model
{
    protected $table = 'staff';

    protected $fillable = [
        'name',
        'phone',
        'role',
        'commission_rate',
        'is_active',
        'user_id',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function schedules()
{
    return $this->hasMany(StaffSchedule::class)->orderBy('day_of_week');
}

public function services()
{
    return $this->belongsToMany(Service::class, 'staff_services');
}

public function appointments()
{
    return $this->hasMany(Appointment::class);
}

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function roleModel(): BelongsTo
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'role', 'name');
    }

    public function getFormattedRoleAttribute(): string
    {
        return Str::title(str_replace('_', ' ', $this->role));
    }

public function isAvailableAt(string $date, string $time, int $durationMinutes): bool
{
    $carbon = \Carbon\Carbon::parse($date);
    $slotStart = \Carbon\Carbon::parse("$date $time");
    $slotEnd = $slotStart->copy()->addMinutes($durationMinutes);

    $schedule = $this->schedules()
        ->where('day_of_week', $carbon->dayOfWeek)
        ->where('is_day_off', false)
        ->first();

    if (!$schedule) return false;

    $workStart = \Carbon\Carbon::parse("$date {$schedule->start_time}");
    $workEnd   = \Carbon\Carbon::parse("$date {$schedule->end_time}");

    if ($slotStart->lt($workStart) || $slotEnd->gt($workEnd)) return false;

    return !$this->appointments()
        ->where('appt_date', $date)
        ->whereNotIn('status', ['cancelled', 'no_show'])
        ->where(fn($q) =>
            $q->whereBetween('start_time', [$slotStart->format('H:i'), $slotEnd->subMinute()->format('H:i')])
              ->orWhereBetween('end_time', [$slotStart->addMinute()->format('H:i'), $slotEnd->format('H:i')])
        )
        ->exists();
}
}
