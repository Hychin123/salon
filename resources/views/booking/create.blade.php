<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>
    <style>
        :root {
            --primary: #6804A5;
            --primary-dark: #4e037d;
            --ink: #1f1b2e;
            --muted: #5b5b6b;
            --surface: #ffffff;
            --line: #e7e5f2;
        }

        * { box-sizing: border-box; }
        body {
            font-family: "Inter", "Segoe UI", Arial, sans-serif;
            background: #f7f4ff;
            background-image:
                radial-gradient(1200px 600px at 8% -10%, #efe6ff 0%, transparent 60%),
                radial-gradient(900px 520px at 110% 10%, #e6f0ff 0%, transparent 55%);
            color: var(--ink);
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            padding: 3.5rem 1.2rem 4.5rem;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background: url("{{ asset('storage/logo-final-01.png') }}") no-repeat center;
            background-size: min(760px, 70%);
            opacity: 0.22;
            pointer-events: none;
            z-index: 0;
        }

        .page {
            width: min(1100px, 100%);
            position: relative;
            z-index: 1;
        }

        .booking-shell {
            display: grid;
            gap: 2rem;
            align-items: start;
        }

        .intro {
            padding: 2.4rem;
            border-radius: 26px;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(104, 4, 165, 0.14);
            box-shadow: 0 35px 80px rgba(39, 30, 74, 0.14);
            backdrop-filter: blur(16px);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: rgba(104, 4, 165, 0.1);
            color: var(--primary);
            font-weight: 600;
            font-size: 0.8rem;
            letter-spacing: 0.3px;
        }

        h1 {
            margin: 0.8rem 0 0.4rem;
            font-size: clamp(2rem, 3vw, 2.7rem);
        }

        .subtitle {
            color: var(--muted);
            margin: 0;
            font-size: 1.02rem;
        }

        .points {
            margin-top: 1.7rem;
            display: grid;
            gap: 1rem;
        }

        .point {
            display: flex;
            gap: 0.8rem;
            align-items: flex-start;
        }

        .point-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            margin-top: 0.45rem;
            background: linear-gradient(135deg, var(--primary), #a855f7);
            box-shadow: 0 0 0 4px rgba(104, 4, 165, 0.12);
            flex-shrink: 0;
        }

        .point-title {
            font-weight: 600;
            color: var(--ink);
        }

        .point-text {
            color: var(--muted);
            font-size: 0.95rem;
            margin-top: 0.2rem;
        }

        .form-stack {
            display: grid;
            gap: 1.1rem;
        }

        form {
            display: grid;
            gap: 1rem 1.2rem;
        }

        label {
            font-weight: 600;
            display: block;
            margin-bottom: 0.35rem;
        }

        input, select, textarea {
            width: 100%;
            padding: 0.7rem 0.8rem;
            border: 1px solid rgba(104, 4, 165, 0.14);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.9);
            color: var(--ink);
            box-shadow: 0 10px 20px rgba(104, 4, 165, 0.05);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: rgba(104, 4, 165, 0.6);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(104, 4, 165, 0.18);
        }

        .card {
            position: relative;
            border: 1px solid rgba(104, 4, 165, 0.16);
            border-radius: 24px;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 30px 70px rgba(39, 30, 74, 0.14);
            backdrop-filter: blur(18px);
        }

        .alert {
            border-radius: 12px;
            padding: 0.85rem 1rem;
            margin-bottom: 1rem;
        }

        .alert ul {
            margin: 0.6rem 0 0 1.1rem;
            padding: 0;
        }

        .alert.ok {
            background: rgba(12, 122, 41, 0.08);
            border: 1px solid rgba(12, 122, 41, 0.25);
            color: #0b7a29;
        }

        .alert.bad {
            background: rgba(180, 35, 24, 0.08);
            border: 1px solid rgba(180, 35, 24, 0.25);
            color: #b42318;
        }

        .ok { color: #0b7a29; }
        .bad { color: #b42318; }
        .muted { color: var(--muted); }

        .availability {
            font-size: 0.95rem;
            padding: 0.35rem 0.2rem 0;
        }

        .actions {
            margin-top: 0.4rem;
        }

        button {
            width: 100%;
            padding: 0.9rem 1rem;
            border: none;
            border-radius: 14px;
            background: linear-gradient(135deg, #5a02a0, #8a2be2);
            color: #ffffff;
            font-weight: 600;
            letter-spacing: 0.2px;
            cursor: pointer;
            box-shadow: 0 12px 24px rgba(104, 4, 165, 0.2);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 26px rgba(104, 4, 165, 0.25);
        }

        @media (min-width: 720px) {
            form {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .full {
                grid-column: 1 / -1;
            }
        }

        @media (min-width: 920px) {
            .booking-shell {
                grid-template-columns: 0.9fr 1.1fr;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="booking-shell">
            <section class="intro">
                <span class="badge">Book Appointment</span>
                <h1>Online Booking</h1>
                <p class="subtitle">Schedule your appointment in just a few steps.</p>

                <div class="points">
                    <div class="point">
                        <span class="point-dot"></span>
                        <div>
                            <div class="point-title">Instant confirmation</div>
                            <div class="point-text">Check availability in real time as you pick a slot.</div>
                        </div>
                    </div>
                    <div class="point">
                        <span class="point-dot"></span>
                        <div>
                            <div class="point-title">Expert care</div>
                            <div class="point-text">Choose services tailored to your needs.</div>
                        </div>
                    </div>
                    <div class="point">
                        <span class="point-dot"></span>
                        <div>
                            <div class="point-title">Fast and secure</div>
                            <div class="point-text">Your booking details stay private and protected.</div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="form-stack">
                @if (session('booking_success'))
                    <p class="alert ok">{{ session('booking_success') }}</p>
                @endif

                @if ($errors->any())
                    <div class="alert bad card">
                        <strong>Please fix the following:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('booking.store') }}" class="card" id="booking-form">
                    @csrf

                    <div class="field">
                        <label for="client_name">Name</label>
                        <input id="client_name" name="client_name" value="{{ old('client_name') }}" required>
                    </div>

                    <div class="field">
                        <label for="client_phone">Phone</label>
                        <input id="client_phone" name="client_phone" value="{{ old('client_phone') }}" required>
                    </div>

                    <div class="field">
                        <label for="client_email">Email (optional)</label>
                        <input id="client_email" name="client_email" type="email" value="{{ old('client_email') }}">
                    </div>

                    <div class="field full">
                        <label for="service_id">Service</label>
                        <select id="service_id" name="service_id" required>
                            <option value="">Select a service</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}" @selected(old('service_id') == $service->id)>
                                    {{ $service->name }} ({{ $service->duration_minutes }} min)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="appt_date">Date</label>
                        <input id="appt_date" name="appt_date" type="date" value="{{ old('appt_date') }}" required>
                    </div>

                    <div class="field">
                        <label for="start_time">Time</label>
                        <input id="start_time" name="start_time" type="time" value="{{ old('start_time') }}" required>
                    </div>

                    <div class="field full">
                        <label for="notes">Notes (optional)</label>
                        <textarea id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                    </div>

                    <div id="availability" class="muted availability full">Pick service/date/time to check availability.</div>

                    <div class="actions full">
                        <button type="submit">Book Now</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const serviceEl = document.getElementById('service_id');
        const dateEl = document.getElementById('appt_date');
        const timeEl = document.getElementById('start_time');
        const outEl = document.getElementById('availability');

        async function checkAvailability() {
            const service_id = serviceEl.value;
            const appt_date = dateEl.value;
            const start_time = timeEl.value;

            if (!service_id || !appt_date || !start_time) {
                outEl.className = 'muted';
                outEl.textContent = 'Pick service/date/time to check availability.';
                return;
            }

            const params = new URLSearchParams({service_id, appt_date, start_time});
            const res = await fetch(`{{ route('booking.availability') }}?${params}`);
            const data = await res.json();

            if (data.available) {
                outEl.className = 'ok';
                outEl.textContent = `Available. Ends at ${data.end_time}. Staff: ${data.staff.join(', ')}. Rooms: ${data.rooms.join(', ')}.`;
                return;
            }

            outEl.className = 'bad';
            outEl.textContent = data.message ?? 'Slot unavailable.';
        }

        [serviceEl, dateEl, timeEl].forEach((el) => el.addEventListener('change', checkAvailability));
    </script>
</body>
</html>
