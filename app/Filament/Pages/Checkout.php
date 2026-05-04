<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\Payment;
use App\Services\PaywayService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Checkout extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static string|\UnitEnum|null $navigationGroup = 'Bookings';
    protected static ?string $navigationLabel = 'POS Checkout';
    protected string $view = 'filament.pages.checkout';

    public ?int $appointment_id = null;
    public float $tip = 0;
    public int $tip_percent = 0;
    public string $method = 'cash';
    public ?Appointment $appointment = null;
    public float $discount = 0;
    public bool $use_loyalty_points = false;
    public int $loyalty_points_to_use = 0;
    public bool $showQrPrompt = false;
    public ?string $payway_tran_id = null;
    public ?string $payway_status = null;
    public ?string $payway_apv = null;
    public array $payway_payload = [];

    public function mount(): void
    {
        $this->appointment_id = request()->integer('appointment');

        if ($this->appointment_id) {
            $this->loadAppointment($this->appointment_id);
            return;
        }

        $firstAppointmentId = Appointment::query()
            ->whereIn('status', ['confirmed', 'in_progress', 'completed'])
            ->whereDoesntHave('payment', fn ($query) => $query->whereNotNull('paid_at'))
            ->orderByDesc('appt_date')
            ->orderByDesc('start_time')
            ->value('id');

        if ($firstAppointmentId) {
            $this->loadAppointment((int) $firstAppointmentId);
        }
    }

    public function getAppointmentsProperty(): Collection
    {
        return Appointment::query()
            ->with(['client', 'staff', 'service'])
            ->whereIn('status', ['confirmed', 'in_progress', 'completed'])
            ->whereDoesntHave('payment', fn ($query) => $query->whereNotNull('paid_at'))
            ->orderByDesc('appt_date')
            ->orderByDesc('start_time')
            ->get();
    }

    public function selectAppointment(int $appointmentId): void
    {
        if ($appointmentId <= 0) {
            return;
        }

        $this->loadAppointment($appointmentId);
    }

    public function setTipPercent(int $percent): void
    {
        $this->tip_percent = $percent;

        $base = $this->appointment ? (float) $this->appointment->total_price : 0.0;
        $this->tip = round($base * ($percent / 100), 2);
    }

    public function updatedTip(): void
    {
        $this->tip_percent = 0;
    }

    public function updatedDiscount($value): void
    {
        $this->discount = max(0, round((float) $value, 2));
    }

    public function updatedUseLoyaltyPoints(bool $value): void
    {
        if (! $value) {
            $this->loyalty_points_to_use = 0;
        }
    }

    public function updatedLoyaltyPointsToUse($value): void
    {
        $this->loyalty_points_to_use = max(0, (int) $value);
    }

    public function getSubtotalProperty(): float
    {
        return $this->appointment ? (float) $this->appointment->total_price : 0.0;
    }

    public function getLoyaltyDiscountProperty(): float
    {
        if (! $this->appointment || ! $this->use_loyalty_points) {
            return 0.0;
        }

        $available = (int) $this->appointment->client->loyalty_points;
        $requested = min($available, $this->loyalty_points_to_use);

        return (float) $requested;
    }

    public function getTotalProperty(): float
    {
        $total = $this->subtotal + $this->tip - $this->discount - $this->loyaltyDiscount;

        return max(0, round($total, 2));
    }

    public function getClientInitialsProperty(): string
    {
        $name = trim((string) ($this->appointment?->client?->name ?? ''));

        if ($name === '') {
            return '?';
        }

        return Str::upper(Str::substr(collect(preg_split('/\s+/', $name) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::substr($part, 0, 1))
            ->implode(''), 0, 2));
    }

    public function processPayment(): void
    {
        if (! $this->appointment) {
            Notification::make()
                ->title('Please select an appointment first.')
                ->warning()
                ->send();

            return;
        }

        if ($this->appointment->payment()->whereNotNull('paid_at')->exists()) {
            Notification::make()
                ->title('This appointment is already paid.')
                ->warning()
                ->send();

            return;
        }

        if ($this->method === 'qr_code') {
            $this->startPaywayCheckout($this->appointment->payment);
            return;
        }

        $this->finalizePayment($this->appointment->payment);
    }

    public function refreshPaywayStatus(): void
    {
        if (! $this->appointment || ! $this->payway_tran_id) {
            return;
        }

        if (! $this->isPaywayConfigured()) {
            Notification::make()
                ->title('PayWay is not configured yet.')
                ->danger()
                ->send();

            return;
        }

        try {
            $response = app(PaywayService::class)->checkTransaction($this->payway_tran_id);
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('Unable to check PayWay status.')
                ->danger()
                ->send();

            return;
        }

        $payment = $this->appointment->payment;
        if (! $payment) {
            return;
        }

        $status = data_get($response, 'data.payment_status');
        $statusCode = data_get($response, 'data.payment_status_code');
        $apv = data_get($response, 'data.apv');

        $payment->fill([
            'payway_status' => $status,
            'payway_apv' => $apv,
        ])->save();

        $this->payway_status = $status;
        $this->payway_apv = $apv;

        if ($status === 'APPROVED' || $statusCode === 0) {
            $this->finalizePayment($payment);
            $this->showQrPrompt = false;
        }
    }

    public function cancelQrPrompt(): void
    {
        $this->showQrPrompt = false;
    }

    #[\Livewire\Attributes\On('payment-success')]
    public function onPaymentSuccess(array $data): void
    {
        $this->refreshPaywayStatus();
    }

    public function getAbaTopupTemplateUrlProperty(): ?string
    {
        $url = trim((string) config('services.aba.topup_template_image_url'));

        return $url !== '' ? $this->resolveImagePathToUrl($url) : null;
    }

    public function getAbaQrImageUrlProperty(): ?string
    {
        $url = trim((string) config('services.aba.qr_image_url'));

        return $url !== '' ? $this->resolveImagePathToUrl($url) : null;
    }

    public function getPaywayPurchaseUrlProperty(): string
    {
        return app(PaywayService::class)->purchaseUrl();
    }

    protected function startPaywayCheckout(?Payment $payment = null): void
    {
        if (! $this->appointment) {
            return;
        }

        if (! $this->isPaywayConfigured()) {
            Notification::make()
                ->title('PayWay is not configured yet.')
                ->danger()
                ->send();

            return;
        }

        $payment = $payment ?: $this->appointment->payment;
        $tranId = $payment?->payway_tran_id ?: $this->generatePaywayTranId();

        if (! $payment) {
            $payment = Payment::create([
                'appointment_id' => $this->appointment->id,
                'amount' => $this->total,
                'tip' => round($this->tip, 2),
                'method' => 'qr_code',
                'payway_tran_id' => $tranId,
                'payway_status' => 'PENDING',
                'payway_requested_at' => now(),
            ]);
        } else {
            $payment->fill([
                'amount' => $this->total,
                'tip' => round($this->tip, 2),
                'method' => 'qr_code',
                'payway_tran_id' => $tranId,
                'payway_status' => $payment->payway_status ?: 'PENDING',
                'payway_requested_at' => now(),
            ])->save();
        }

        $this->payway_payload = $this->buildPaywayPayload($tranId);
        $this->payway_tran_id = $tranId;
        $this->payway_status = $payment->payway_status;
        $this->payway_apv = $payment->payway_apv;
        $this->showQrPrompt = true;
    }

    protected function buildPaywayPayload(string $tranId): array
    {
        $client = $this->appointment?->client;
        [$firstName, $lastName] = $this->splitName((string) ($client?->name ?? ''));
        $paymentOption = trim((string) config('services.payway.payment_option'));
        if ($paymentOption === '') {
            $paymentOption = 'abapay_khqr';
        }

        $payload = [
            'req_time' => now()->format('YmdHis'),
            'merchant_id' => trim((string) config('services.payway.merchant_id')),
            'tran_id' => $tranId,
            'amount' => number_format($this->total, 2, '.', ''),
            'currency' => trim((string) config('services.payway.currency', 'USD')),
            'payment_option' => $paymentOption,
            'return_url' => trim((string) config('services.payway.return_url')),
            'return_params' => 'appointment_id=' . $this->appointment?->id,
            'firstname' => $firstName,
            'lastname' => $lastName,
            'phone' => $client?->phone,
            'email' => $client?->email,
        ];

        return app(PaywayService::class)->buildPurchasePayload($payload);
    }

    protected function generatePaywayTranId(): string
    {
        $suffix = str_pad((string) ($this->appointment?->id ?? 0), 4, '0', STR_PAD_LEFT);

        return now()->format('ymdHis') . $suffix;
    }

    protected function isPaywayConfigured(): bool
    {
        return filled(config('services.payway.merchant_id')) && filled(config('services.payway.api_key'));
    }

    protected function splitName(string $name): array
    {
        $parts = array_values(array_filter(preg_split('/\s+/', trim($name)) ?: []));

        if ($parts === []) {
            return ['', ''];
        }

        $first = array_shift($parts);
        $last = implode(' ', $parts);

        return [$first, $last];
    }

    protected function finalizePayment(?Payment $payment = null): void
    {
        if (! $this->appointment) {
            return;
        }

        $client = $this->appointment->client;
        $loyaltyUsed = (int) $this->loyaltyDiscount;

        $method = $payment?->method ?? $this->method;

        if (! $payment) {
            $payment = Payment::create([
                'appointment_id' => $this->appointment->id,
                'amount' => $this->total,
                'tip' => round($this->tip, 2),
                'method' => $method,
                'paid_at' => now(),
                'payway_status' => $method === 'qr_code' ? ($this->payway_status ?: 'APPROVED') : null,
                'payway_apv' => $this->payway_apv,
            ]);
        } else {
            $payment->fill([
                'amount' => $this->total,
                'tip' => round($this->tip, 2),
                'method' => $method,
                'paid_at' => now(),
                'payway_status' => $method === 'qr_code' ? ($this->payway_status ?: 'APPROVED') : $payment->payway_status,
                'payway_apv' => $this->payway_apv ?: $payment->payway_apv,
            ])->save();
        }

        $this->appointment->update(['status' => 'completed']);

        if ($loyaltyUsed > 0) {
            $client->decrement('loyalty_points', $loyaltyUsed);
        }

        $pointsEarned = (int) floor($this->total);
        if ($pointsEarned > 0) {
            $client->increment('loyalty_points', $pointsEarned);
        }

        Notification::make()
            ->title('Payment of $' . number_format($this->total, 2) . ' confirmed!')
            ->success()
            ->send();

        $this->loadAppointment($this->appointment->id);
    }

    public function printReceipt(): StreamedResponse
    {
        abort_unless($this->appointment, 404);

        $content = $this->buildReceiptText();
        $filename = 'receipt-appointment-' . $this->appointment->id . '.txt';

        return response()->streamDownload(function () use ($content): void {
            echo $content;
        }, $filename, ['Content-Type' => 'text/plain']);
    }

    public function emailReceipt(): void
    {
        if (! $this->appointment || ! $this->appointment->client?->email) {
            Notification::make()
                ->title('Client email is missing.')
                ->danger()
                ->send();

            return;
        }

        $content = $this->buildReceiptText();

        Mail::raw($content, function ($message): void {
            $message
                ->to($this->appointment->client->email)
                ->subject('Your salon receipt #' . $this->appointment->id);
        });

        Notification::make()
            ->title('Receipt emailed successfully.')
            ->success()
            ->send();
    }

    protected function loadAppointment(int $appointmentId): void
    {
        $this->appointment = Appointment::with(['client', 'staff', 'service', 'payment'])
            ->find($appointmentId);

        $this->appointment_id = $this->appointment?->id;
        $this->tip = 0;
        $this->tip_percent = 0;
        $this->discount = 0;
        $this->use_loyalty_points = false;
        $this->loyalty_points_to_use = 0;
        $this->method = $this->appointment?->payment?->method ?? 'cash';
        $this->showQrPrompt = false;
        $this->payway_tran_id = $this->appointment?->payment?->payway_tran_id;
        $this->payway_status = $this->appointment?->payment?->payway_status;
        $this->payway_apv = $this->appointment?->payment?->payway_apv;
        $this->payway_payload = [];
    }

    protected function buildReceiptText(): string
    {
        $appointment = $this->appointment;
        $client = $appointment?->client;
        $staff = $appointment?->staff;
        $service = $appointment?->service;

        $lines = [
            'SALON RECEIPT',
            str_repeat('-', 40),
            'Receipt date: ' . Carbon::now()->format('Y-m-d H:i'),
            'Appointment #: ' . $appointment?->id,
            'Client: ' . ($client?->name ?? '-'),
            'Service: ' . ($service?->name ?? '-'),
            'Staff: ' . ($staff?->name ?? '-'),
            'Date: ' . ($appointment?->appt_date?->format('Y-m-d') ?? '-'),
            'Time: ' . ($appointment ? Carbon::parse($appointment->start_time)->format('H:i') : '-'),
            str_repeat('-', 40),
            'Subtotal: $' . number_format($this->subtotal, 2),
            'Tip: $' . number_format($this->tip, 2),
            'Discount: -$' . number_format($this->discount, 2),
            'Loyalty: -$' . number_format($this->loyaltyDiscount, 2),
            'Total Paid: $' . number_format($this->total, 2),
            'Method: ' . strtoupper(str_replace('_', ' ', $this->method)),
            str_repeat('-', 40),
            'Thank you for your visit!',
        ];

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    private function resolveImagePathToUrl(string $input): string
    {
        if (str_starts_with($input, 'http://') || str_starts_with($input, 'https://')) {
            return $input;
        }

        $normalized = ltrim($input, '/');

        if (str_starts_with($normalized, 'storage/')) {
            return url('/' . $normalized);
        }

        if (str_starts_with($normalized, 'public/')) {
            return Storage::url(substr($normalized, 7));
        }

        if (str_starts_with($normalized, 'private/')) {
            return route('aba.image.private', ['path' => substr($normalized, 8)]);
        }

        return url('/storage/' . $normalized);
    }

    public static function getNavigationGroup(): string |\UnitEnum | null
    {
        return 'Bookings';
    }
}
