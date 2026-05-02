<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Room;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function calculateEndTime(?string $startTime, ?int $serviceId): ?string
    {
        if (! $startTime || ! $serviceId) {
            return null;
        }

        $service = Service::find($serviceId);

        if (! $service?->duration_minutes) {
            return null;
        }

        return Carbon::parse($startTime)->addMinutes((int) $service->duration_minutes)->format('H:i:s');
    }

    public function normalizeTime(string $time): string
    {
        return Carbon::parse($time)->format('H:i:s');
    }

    public function getAvailableStaffOptions(
        ?int $serviceId,
        ?string $date,
        ?string $startTime,
        ?string $endTime,
        ?int $ignoreAppointmentId = null,
        ?int $selectedStaffId = null
    ): array {
        if (! $serviceId || ! $date || ! $startTime || ! $endTime) {
            return [];
        }

        $startTime = $this->normalizeTime($startTime);
        $endTime = $this->normalizeTime($endTime);

        $staffQuery = Staff::query()
            ->where('is_active', true)
            ->whereHas('services', fn (Builder $query) => $query->whereKey($serviceId))
            ->orderBy('name');

        $options = [];

        $staffQuery->get()->each(function (Staff $staff) use (&$options, $date, $startTime, $endTime, $ignoreAppointmentId): void {
            if ($this->isStaffAvailableForSlot($staff->id, $date, $startTime, $endTime, $ignoreAppointmentId)) {
                $options[$staff->id] = $staff->name;
            }
        });

        if ($selectedStaffId && ! array_key_exists($selectedStaffId, $options)) {
            $selectedStaff = Staff::find($selectedStaffId);
            if ($selectedStaff) {
                $options[$selectedStaff->id] = $selectedStaff->name . ' (currently unavailable)';
            }
        }

        return $options;
    }

    public function getAvailableRoomOptions(?string $date, ?string $startTime, ?string $endTime, ?int $ignoreAppointmentId = null): array
    {
        if (! $date || ! $startTime || ! $endTime) {
            return [];
        }

        $startTime = $this->normalizeTime($startTime);
        $endTime = $this->normalizeTime($endTime);

        $rooms = Room::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $options = [];

        foreach ($rooms as $room) {
            if ($this->isRoomAvailableForSlot($room->id, $date, $startTime, $endTime, $ignoreAppointmentId)) {
                $options[$room->id] = $room->name;
            }
        }

        return $options;
    }

    public function isStaffAvailableForSlot(
        int $staffId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $ignoreAppointmentId = null
    ): bool {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        $schedule = StaffSchedule::query()
            ->where('staff_id', $staffId)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (! $schedule || $schedule->is_day_off) {
            return false;
        }

        if (! $schedule->start_time || ! $schedule->end_time) {
            return false;
        }

        $startTime = $this->normalizeTime($startTime);
        $endTime = $this->normalizeTime($endTime);

        if ($startTime < $this->normalizeTime($schedule->start_time) || $endTime > $this->normalizeTime($schedule->end_time)) {
            return false;
        }

        $hasOverlap = Appointment::query()
            ->where('staff_id', $staffId)
            ->whereDate('appt_date', $date)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->when($ignoreAppointmentId, fn (Builder $query) => $query->whereKeyNot($ignoreAppointmentId))
            ->where(function (Builder $query) use ($startTime, $endTime): void {
                $query
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->exists();

        return ! $hasOverlap;
    }

    public function isRoomAvailableForSlot(
        int $roomId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $ignoreAppointmentId = null
    ): bool {
        $startTime = $this->normalizeTime($startTime);
        $endTime = $this->normalizeTime($endTime);

        $hasOverlap = Appointment::query()
            ->where('room_id', $roomId)
            ->whereDate('appt_date', $date)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->when($ignoreAppointmentId, fn (Builder $query) => $query->whereKeyNot($ignoreAppointmentId))
            ->where(function (Builder $query) use ($startTime, $endTime): void {
                $query
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->exists();

        return ! $hasOverlap;
    }

    public function ensureAppointmentIsValid(array $data, ?int $ignoreAppointmentId = null): array
    {
        $required = ['service_id', 'appt_date', 'start_time', 'staff_id', 'room_id'];

        foreach ($required as $key) {
            if (! filled($data[$key] ?? null)) {
                throw ValidationException::withMessages([
                    $key => ucfirst(str_replace('_', ' ', $key)) . ' is required.',
                ]);
            }
        }

        $startTime = $this->normalizeTime((string) $data['start_time']);
        $endTime = filled($data['end_time'] ?? null)
            ? $this->normalizeTime((string) $data['end_time'])
            : $this->calculateEndTime($startTime, (int) $data['service_id']);

        if (! $endTime || $startTime >= $endTime) {
            throw ValidationException::withMessages([
                'start_time' => 'Start time must be before end time.',
            ]);
        }

        $service = Service::query()->find((int) $data['service_id']);

        if (! $service || ! $service->is_active) {
            throw ValidationException::withMessages([
                'service_id' => 'Selected service is not available.',
            ]);
        }

        $staffHasService = Staff::query()
            ->whereKey((int) $data['staff_id'])
            ->where('is_active', true)
            ->whereHas('services', fn (Builder $query) => $query->whereKey((int) $data['service_id']))
            ->exists();

        if (! $staffHasService) {
            throw ValidationException::withMessages([
                'staff_id' => 'Selected staff does not provide this service.',
            ]);
        }

        if (! $this->isStaffAvailableForSlot((int) $data['staff_id'], (string) $data['appt_date'], $startTime, $endTime, $ignoreAppointmentId)) {
            throw ValidationException::withMessages([
                'staff_id' => 'Selected staff is not available at this time.',
            ]);
        }

        $room = Room::query()
            ->whereKey((int) $data['room_id'])
            ->where('is_active', true)
            ->first();

        if (! $room) {
            throw ValidationException::withMessages([
                'room_id' => 'Selected room is not available.',
            ]);
        }

        if (! $this->isRoomAvailableForSlot((int) $data['room_id'], (string) $data['appt_date'], $startTime, $endTime, $ignoreAppointmentId)) {
            throw ValidationException::withMessages([
                'room_id' => 'Selected room is already occupied for this time slot.',
            ]);
        }

        $data['start_time'] = $startTime;
        $data['end_time'] = $endTime;
        $data['total_price'] = (float) ($data['total_price'] ?? $service->price);

        return $data;
    }

    public function findOrCreateClient(string $name, string $phone, ?string $email = null, ?string $notes = null): Client
    {
        $existing = Client::query()
            ->withTrashed()
            ->where('phone', $phone)
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            $existing->fill([
                'name' => $name,
                'email' => $email ?: $existing->email,
                'notes' => $notes ?: $existing->notes,
            ]);
            $existing->save();

            return $existing;
        }

        return Client::query()->create([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'notes' => $notes,
        ]);
    }

    public function createAppointment(array $data): Appointment
    {
        $validated = $this->ensureAppointmentIsValid($data);

        return Appointment::query()->create([
            'client_id' => (int) $validated['client_id'],
            'staff_id' => (int) $validated['staff_id'],
            'service_id' => (int) $validated['service_id'],
            'room_id' => (int) $validated['room_id'],
            'appt_date' => (string) $validated['appt_date'],
            'start_time' => (string) $validated['start_time'],
            'end_time' => (string) $validated['end_time'],
            'status' => (string) ($validated['status'] ?? 'confirmed'),
            'total_price' => (float) $validated['total_price'],
            'notes' => $validated['notes'] ?? null,
        ]);
    }

    public function createOnlineBooking(array $payload): Appointment
    {
        $service = Service::query()
            ->whereKey((int) $payload['service_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $startTime = $this->normalizeTime((string) $payload['start_time']);
        $endTime = $this->calculateEndTime($startTime, (int) $service->id);

        if (! $endTime) {
            throw ValidationException::withMessages([
                'service_id' => 'Unable to compute appointment duration for this service.',
            ]);
        }

        $staffOptions = $this->getAvailableStaffOptions(
            (int) $service->id,
            (string) $payload['appt_date'],
            $startTime,
            $endTime,
        );

        if ($staffOptions === []) {
            throw ValidationException::withMessages([
                'start_time' => 'No staff is available for that slot.',
            ]);
        }

        $roomOptions = $this->getAvailableRoomOptions(
            (string) $payload['appt_date'],
            $startTime,
            $endTime,
        );

        if ($roomOptions === []) {
            throw ValidationException::withMessages([
                'start_time' => 'No room is available for that slot.',
            ]);
        }

        $client = $this->findOrCreateClient(
            (string) $payload['client_name'],
            (string) $payload['client_phone'],
            $payload['client_email'] ?? null,
            $payload['notes'] ?? null,
        );

        return $this->createAppointment([
            'client_id' => $client->id,
            'staff_id' => (int) array_key_first($staffOptions),
            'room_id' => (int) array_key_first($roomOptions),
            'service_id' => (int) $service->id,
            'appt_date' => (string) $payload['appt_date'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'confirmed',
            'total_price' => (float) $service->price,
            'notes' => trim('[online] ' . (string) ($payload['notes'] ?? '')),
        ]);
    }

    public function createWalkInBooking(array $payload): Appointment
    {
        $service = Service::query()
            ->whereKey((int) $payload['service_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $startTime = $this->normalizeTime((string) $payload['start_time']);
        $endTime = $this->calculateEndTime($startTime, (int) $service->id);

        if (! $endTime) {
            throw ValidationException::withMessages([
                'service_id' => 'Unable to compute appointment duration for this service.',
            ]);
        }

        $staffId = isset($payload['staff_id']) ? (int) $payload['staff_id'] : null;
        $roomId = isset($payload['room_id']) ? (int) $payload['room_id'] : null;

        if (! $staffId) {
            $staffOptions = $this->getAvailableStaffOptions(
                (int) $service->id,
                (string) $payload['appt_date'],
                $startTime,
                $endTime,
            );

            if ($staffOptions === []) {
                throw ValidationException::withMessages([
                    'start_time' => 'No staff is available for that slot.',
                ]);
            }

            $staffId = (int) array_key_first($staffOptions);
        }

        if (! $roomId) {
            $roomOptions = $this->getAvailableRoomOptions(
                (string) $payload['appt_date'],
                $startTime,
                $endTime,
            );

            if ($roomOptions === []) {
                throw ValidationException::withMessages([
                    'start_time' => 'No room is available for that slot.',
                ]);
            }

            $roomId = (int) array_key_first($roomOptions);
        }

        $client = $this->findOrCreateClient(
            (string) $payload['client_name'],
            (string) $payload['client_phone'],
            $payload['client_email'] ?? null,
            $payload['notes'] ?? null,
        );

        return $this->createAppointment([
            'client_id' => $client->id,
            'staff_id' => $staffId,
            'room_id' => $roomId,
            'service_id' => (int) $service->id,
            'appt_date' => (string) $payload['appt_date'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => (string) ($payload['status'] ?? 'confirmed'),
            'total_price' => (float) ($payload['total_price'] ?? $service->price),
            'notes' => trim('[walk-in] ' . (string) ($payload['notes'] ?? '')),
        ]);
    }

    public function getUnavailableRoomReason(string $date, string $startTime, string $endTime): string
    {
        $activeRoomsCount = Room::query()
            ->where('is_active', true)
            ->count();

        if ($activeRoomsCount === 0) {
            return 'No active rooms configured. Please add rooms first.';
        }

        return 'No rooms are available for this slot. Try another time.';
    }
}
