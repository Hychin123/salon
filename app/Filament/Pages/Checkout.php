<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\Payment;
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

    public function mount(): void
    {
        $this->appointment_id = request()->integer('appointment');

        if ($this->appointment_id) {
            $this->loadAppointment($this->appointment_id);
            return;
        }

        $firstAppointmentId = Appointment::query()
            ->whereIn('status', ['confirmed', 'in_progress', 'completed'])
            ->whereDoesntHave('payment')
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
            ->whereDoesntHave('payment')
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

        if ($this->appointment->payment()->exists()) {
            Notification::make()
                ->title('This appointment is already paid.')
                ->warning()
                ->send();

            return;
        }

        if ($this->method === 'qr_code') {
            $this->showQrPrompt = true;

            return;
        }

        $this->finalizePayment();
    }

    public function confirmQrPaid(): void
    {
        if (! $this->appointment) {
            return;
        }

        $this->finalizePayment();
        $this->showQrPrompt = false;
    }

    public function cancelQrPrompt(): void
    {
        $this->showQrPrompt = false;
    }

    public function getQrImageUrlProperty(): ?string
    {
        if (! $this->showQrPrompt) {
            return null;
        }

        $customUrl = trim((string) config('services.aba.qr_image_url'));
        if ($customUrl !== '') {
            return $this->resolveImagePathToUrl($customUrl);
        }

        return 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=' . urlencode($this->abaDeepLink);
    }

    public function getAbaTopupTemplateUrlProperty(): ?string
    {
        $url = trim((string) config('services.aba.topup_hitemplate_image_url'));

        return $url !== '' ? $this->resolveImagePathToUrl($url) : null;
    }

    public function getQrHintProperty(): string
    {
        $merchant = trim((string) config('services.aba.merchant_name', 'Salon Payment'));

        return "{$merchant} · USD " . number_format($this->total, 2);
    }

    public function getAbaDeepLinkProperty(): string
    {
        $base = trim((string) config('services.aba.deeplink_base', ''));
        $merchantId = trim((string) config('services.aba.merchant_id'));
        $account = trim((string) config('services.aba.account'));
        $currency = trim((string) config('services.aba.currency', 'USD'));
        $merchantName = trim((string) config('services.aba.merchant_name', 'Salon Payment'));
        $txnId = 'APPT-' . ($this->appointment?->id ?? 'NA') . '-' . now()->format('YmdHis');

        if ($base === '') {
            return '';
        }

        $params = array_filter([
            'amount' => number_format($this->total, 2, '.', ''),
            'ccy' => $currency,
            'merchant' => $merchantName,
            'merchant_id' => $merchantId ?: null,
            'account' => $account ?: null,
            'memo' => 'Checkout #' . ($this->appointment?->id ?? ''),
            'txn_id' => $txnId,
        ], fn ($value) => filled($value));

        return $base . (str_contains($base, '?') ? '&' : '?') . http_build_query($params);
    }

    public function getCanOpenAbaLinkProperty(): bool
    {
        $base = trim((string) config('services.aba.deeplink_base', ''));

        return $base !== '' && filled($this->abaDeepLink);
    }

    protected function finalizePayment(): void
    {
        if (! $this->appointment) {
            return;
        }

        $client = $this->appointment->client;
        $loyaltyUsed = (int) $this->loyaltyDiscount;

        Payment::create([
            'appointment_id' => $this->appointment->id,
            'amount' => $this->total,
            'tip' => round($this->tip, 2),
            'method' => $this->method,
            'paid_at' => now(),
        ]);

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
        $this->method = 'cash';
        $this->showQrPrompt = false;
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
