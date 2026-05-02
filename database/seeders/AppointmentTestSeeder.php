<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AppointmentTestSeeder extends Seeder
{
    public function run(): void
    {
        $services = collect([
            ['name' => 'Thai Massage', 'category' => 'massage', 'duration_minutes' => 60, 'price' => 40, 'is_active' => true],
            ['name' => 'Head & Shoulder Massage', 'category' => 'massage', 'duration_minutes' => 45, 'price' => 30, 'is_active' => true],
            ['name' => 'Hair Cut', 'category' => 'hair', 'duration_minutes' => 40, 'price' => 15, 'is_active' => true],
            ['name' => 'Hair Coloring', 'category' => 'hair', 'duration_minutes' => 90, 'price' => 45, 'is_active' => true],
            ['name' => 'Armpit Waxing', 'category' => 'waxing', 'duration_minutes' => 20, 'price' => 10, 'is_active' => true],
        ])->map(fn (array $data) => Service::query()->firstOrCreate(['name' => $data['name']], $data));

        $rooms = collect([
            ['name' => 'A1', 'is_active' => true],
            ['name' => 'A2', 'is_active' => true],
            ['name' => 'B1', 'is_active' => true],
        ])->map(fn (array $data) => Room::query()->firstOrCreate(['name' => $data['name']], $data));

        $staff = collect([
            ['name' => 'Sopheap', 'phone' => '010100100', 'role' => 'therapist', 'commission_rate' => 20, 'is_active' => true],
            ['name' => 'Maly', 'phone' => '010100101', 'role' => 'therapist', 'commission_rate' => 18, 'is_active' => true],
            ['name' => 'Rongrath', 'phone' => '010100102', 'role' => 'stylist', 'commission_rate' => 15, 'is_active' => true],
            ['name' => 'Dara', 'phone' => '010100103', 'role' => 'stylist', 'commission_rate' => 16, 'is_active' => true],
        ])->map(fn (array $data) => Staff::query()->firstOrCreate(['name' => $data['name']], $data));

        $staff[0]->services()->syncWithoutDetaching([$services[0]->id, $services[1]->id]);
        $staff[1]->services()->syncWithoutDetaching([$services[0]->id, $services[4]->id]);
        $staff[2]->services()->syncWithoutDetaching([$services[2]->id, $services[3]->id, $services[4]->id]);
        $staff[3]->services()->syncWithoutDetaching([$services[2]->id, $services[3]->id]);

        foreach ($staff as $member) {
            for ($day = 0; $day <= 6; $day++) {
                StaffSchedule::query()->updateOrCreate(
                    ['staff_id' => $member->id, 'day_of_week' => $day],
                    [
                        'is_day_off' => $day === 0,
                        'start_time' => $day === 0 ? null : '09:00:00',
                        'end_time' => $day === 0 ? null : '19:00:00',
                    ]
                );
            }
        }

        $clients = collect([
            ['name' => 'Sokha Lim', 'phone' => '011000001', 'email' => 'sokha@example.com', 'allergy_notes' => 'No oils with fragrance', 'health_notes' => 'Lower back pain', 'loyalty_points' => 25],
            ['name' => 'Vanna Keo', 'phone' => '011000002', 'email' => 'vanna@example.com', 'allergy_notes' => null, 'health_notes' => null, 'loyalty_points' => 40],
            ['name' => 'Nita Chum', 'phone' => '011000003', 'email' => 'nita@example.com', 'allergy_notes' => 'Sensitive skin', 'health_notes' => null, 'loyalty_points' => 8],
            ['name' => 'Pisey Orn', 'phone' => '011000004', 'email' => 'pisey@example.com', 'allergy_notes' => null, 'health_notes' => 'Pregnant (2nd trimester)', 'loyalty_points' => 15],
            ['name' => 'Kosal Yin', 'phone' => '011000005', 'email' => 'kosal@example.com', 'allergy_notes' => null, 'health_notes' => null, 'loyalty_points' => 2],
        ])->map(function (array $data) {
            return Client::query()->updateOrCreate(
                ['phone' => $data['phone']],
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'allergy_notes' => $data['allergy_notes'],
                    'health_notes' => $data['health_notes'],
                    'loyalty_points' => $data['loyalty_points'],
                ]
            );
        });

        $clients[0]->preferredServices()->syncWithoutDetaching([$services[0]->id, $services[1]->id]);
        $clients[1]->preferredServices()->syncWithoutDetaching([$services[2]->id, $services[3]->id]);
        $clients[2]->preferredServices()->syncWithoutDetaching([$services[4]->id]);

        $statuses = ['pending', 'confirmed', 'in_progress', 'completed', 'completed', 'cancelled', 'no_show'];
        $timeSlots = ['09:00:00', '10:30:00', '12:00:00', '14:00:00', '16:00:00'];

        for ($i = 0; $i < 22; $i++) {
            $client = $clients[$i % $clients->count()];
            $service = $services[$i % $services->count()];
            $room = $rooms[$i % $rooms->count()];

            $eligibleStaff = $staff->filter(fn (Staff $s) => $s->services->contains('id', $service->id))->values();
            $staffMember = $eligibleStaff[$i % max(1, $eligibleStaff->count())];

            $date = Carbon::today()->subDays(5)->addDays($i % 12);
            while ($date->dayOfWeek === 0) {
                $date->addDay();
            }

            $startTime = $timeSlots[$i % count($timeSlots)];
            $endTime = Carbon::parse($startTime)->addMinutes((int) $service->duration_minutes)->format('H:i:s');
            $status = $statuses[$i % count($statuses)];

            $appointment = Appointment::query()->create([
                'client_id' => $client->id,
                'staff_id' => $staffMember->id,
                'service_id' => $service->id,
                'room_id' => $room->id,
                'appt_date' => $date->toDateString(),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => $status,
                'total_price' => $service->price,
                'notes' => "Seeder test appointment #{$i}",
            ]);

            if ($status === 'completed') {
                Payment::query()->create([
                    'appointment_id' => $appointment->id,
                    'amount' => $appointment->total_price,
                    'tip' => ($i % 3) * 2.5,
                    'method' => ['cash', 'card', 'qr_code'][$i % 3],
                    'paid_at' => $date->copy()->setTime(18, 0),
                ]);
            }
        }
    }
}
