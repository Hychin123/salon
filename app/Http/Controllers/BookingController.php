<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookingService)
    {
    }

    public function create(): View
    {
        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'duration_minutes', 'price']);

        return view('booking.create', compact('services'));
    }

    public function availability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'appt_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
        ]);

        $endTime = $this->bookingService->calculateEndTime($validated['start_time'], (int) $validated['service_id']);

        if (! $endTime) {
            return response()->json([
                'available' => false,
                'message' => 'Unable to calculate end time for selected service.',
                'staff' => [],
                'rooms' => [],
            ], 422);
        }

        $staff = $this->bookingService->getAvailableStaffOptions(
            (int) $validated['service_id'],
            $validated['appt_date'],
            $validated['start_time'],
            $endTime,
        );

        $rooms = $this->bookingService->getAvailableRoomOptions(
            $validated['appt_date'],
            $validated['start_time'],
            $endTime,
        );

        return response()->json([
            'available' => $staff !== [] && $rooms !== [],
            'staff' => array_values($staff),
            'rooms' => array_values($rooms),
            'end_time' => $endTime,
            'message' => ($staff !== [] && $rooms !== [])
                ? 'Slot is available.'
                : 'Slot unavailable. Please try another date/time.',
        ]);
    }

    public function storeOnline(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_name' => ['required', 'string', 'max:255'],
            'client_phone' => ['required', 'string', 'max:50'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'appt_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string'],
        ]);

        $appointment = $this->bookingService->createOnlineBooking($validated);

        $startTime = \Illuminate\Support\Carbon::parse($appointment->start_time)->format('H:i');

        return redirect()
            ->route('booking.create')
            ->with('booking_success', "Booking confirmed for {$appointment->appt_date->format('Y-m-d')} at {$startTime}.");
    }

    public function storeWalkIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_name' => ['required', 'string', 'max:255'],
            'client_phone' => ['required', 'string', 'max:50'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'appt_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'staff_id' => ['nullable', 'integer', 'exists:staff,id'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'status' => ['nullable', 'in:pending,confirmed,in_progress,completed,cancelled,no_show'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $appointment = $this->bookingService->createWalkInBooking($validated);

        return response()->json([
            'message' => 'Walk-in booking confirmed.',
            'appointment_id' => $appointment->id,
            'staff_id' => $appointment->staff_id,
            'room_id' => $appointment->room_id,
            'start_time' => $appointment->start_time,
            'end_time' => $appointment->end_time,
        ], 201);
    }
}
