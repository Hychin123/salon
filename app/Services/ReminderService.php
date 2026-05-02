<?php

namespace App\Services;

use App\Mail\AppointmentReminderMail;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client as TwilioClient;

class ReminderService
{
    public function dispatch24HourReminders(): int
    {
        $start = Carbon::now()->addHours(24)->startOfMinute();
        $end = Carbon::now()->addHours(24)->addMinute()->startOfMinute();

        $appointments = Appointment::query()
            ->with(['client', 'staff', 'service', 'room'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereNull('reminder_sent_at')
            ->whereBetween('appt_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->filter(function (Appointment $appointment) use ($start, $end): bool {
                $appointmentAt = Carbon::parse($appointment->appt_date->format('Y-m-d') . ' ' . $appointment->start_time);

                return $appointmentAt->greaterThanOrEqualTo($start) && $appointmentAt->lessThan($end);
            });

        $sent = 0;

        foreach ($appointments as $appointment) {
            $emailSent = $this->sendEmailReminder($appointment);
            $smsSent = $this->sendSmsReminder($appointment);

            if ($emailSent || $smsSent) {
                $appointment->update([
                    'reminder_sent_at' => now(),
                ]);

                $sent++;
                continue;
            }

            Log::warning('Reminder dispatch failed for appointment.', [
                'appointment_id' => $appointment->id,
            ]);
        }

        return $sent;
    }

    private function sendEmailReminder(Appointment $appointment): bool
    {
        $to = $appointment->client->email;
        if (! $to) {
            return false;
        }

        try {
            Mail::to($to)->send(new AppointmentReminderMail($appointment));
        } catch (Throwable $exception) {
            Log::error('Email reminder failed.', [
                'appointment_id' => $appointment->id,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    private function sendSmsReminder(Appointment $appointment): bool
    {
        $sid = (string) env('TWILIO_SID', '');
        $token = (string) env('TWILIO_TOKEN', '');
        $from = (string) env('TWILIO_FROM', '');
        $to = (string) ($appointment->client->phone ?? '');

        if ($sid === '' || $token === '' || $from === '' || $to === '') {
            return false;
        }

        try {
            $client = new TwilioClient($sid, $token);
            $client->messages->create($to, [
                'from' => $from,
                'body' => sprintf(
                    'Reminder: %s with %s on %s at %s.',
                    $appointment->service->name,
                    $appointment->staff->name,
                    $appointment->appt_date->format('Y-m-d'),
                    Carbon::parse($appointment->start_time)->format('H:i')
                ),
            ]);
        } catch (TwilioException $exception) {
            Log::error('Twilio reminder failed.', [
                'appointment_id' => $appointment->id,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }

        return true;
    }
}
