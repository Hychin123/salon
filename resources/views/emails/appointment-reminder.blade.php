<p>Hello {{ $appointment->client->name }},</p>

<p>This is a reminder for your appointment in about 24 hours.</p>

<p>
    Service: {{ $appointment->service->name }}<br>
    Staff: {{ $appointment->staff->name }}<br>
    Date: {{ $appointment->appt_date->format('Y-m-d') }}<br>
    Time: {{ \Illuminate\Support\Carbon::parse($appointment->start_time)->format('H:i') }} - {{ \Illuminate\Support\Carbon::parse($appointment->end_time)->format('H:i') }}<br>
    Room: {{ $appointment->room?->name ?? 'TBA' }}
</p>

<p>We look forward to seeing you.</p>
