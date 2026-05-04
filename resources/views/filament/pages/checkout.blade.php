<x-filament-panels::page>
    <style>
        .pos-shell { margin-top: 1rem; }
        .pos-switch-wrap { margin-bottom: 1rem; }
        .pos-switch-label { display: block; margin-bottom: .35rem; font-size: .95rem; color: #4b5563; font-weight: 600; }
        .pos-switch-select {
            width: 100%;
            max-width: 28rem;
            padding: .5rem .75rem;
            border: 1px solid #d1d5db;
            border-radius: .625rem;
            background: #ffffff;
            color: #111827;
        }

        .pos-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        @media (min-width: 1100px) {
            .pos-layout {
                grid-template-columns: 1.6fr 1fr;
            }
        }

        .pos-card {
            background: linear-gradient(180deg, #2e2f33 0%, #2a2b2f 100%);
            border: 1px solid #4a4b50;
            border-radius: 1.25rem;
            padding: 1.5rem;
            color: #f3f4f6;
        }

        .pos-row { display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
        .pos-row-start { display: flex; align-items: center; gap: 1rem; }
        .pos-avatar {
            width: 4rem;
            height: 4rem;
            border-radius: 9999px;
            background: #d5ece8;
            color: #0f5b56;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.65rem;
        }

        .pos-name { font-size: 2rem; font-weight: 700; line-height: 1.1; }
        .pos-subtitle { margin-top: .2rem; font-size: 1.05rem; color: #b7b8bc; }
        .pos-badge {
            background: #2d6a1f;
            color: #d6f7bf;
            border-radius: .6rem;
            padding: .55rem .95rem;
            font-weight: 700;
            font-size: 1rem;
            text-transform: lowercase;
        }
        .pos-divider { border: 0; border-top: 1px solid #4a4b50; margin: 1.35rem 0; }
        .pos-title { font-size: 2rem; font-weight: 700; margin-bottom: 1rem; }
        .pos-item-name { font-size: 2rem; font-weight: 600; line-height: 1.15; }
        .pos-item-sub { color: #b7b8bc; font-size: 1.15rem; }
        .pos-price { font-size: 2rem; font-weight: 700; }

        .pos-tip-title { margin-top: 1.4rem; margin-bottom: .7rem; font-size: 2rem; font-weight: 700; }
        .pos-tip-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .75rem;
        }
        @media (min-width: 850px) {
            .pos-tip-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }

        .pos-btn {
            border: 1px solid #66676b;
            border-radius: .95rem;
            background: transparent;
            color: #f3f4f6;
            padding: .85rem .8rem;
            font-weight: 700;
            font-size: 1.05rem;
            text-align: center;
            cursor: pointer;
        }
        .pos-btn:hover { border-color: #83858a; }
        .pos-btn.active { background: #3a3b3f; border-color: #8b8d93; }

        .pos-input {
            width: 100%;
            border: 1px solid #66676b;
            border-radius: .95rem;
            background: transparent;
            color: #ffffff;
            padding: .85rem .8rem;
            font-weight: 600;
            text-align: center;
        }

        .pos-panel-title { font-size: 2rem; font-weight: 700; margin-bottom: .9rem; }
        .pos-method-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem; }
        .pos-summary-title { font-size: 2rem; font-weight: 700; margin: 1.5rem 0 .6rem; }
        .pos-summary-row { display: flex; justify-content: space-between; font-size: 1.15rem; color: #b7b8bc; margin-bottom: .35rem; }
        .pos-total-row { display: flex; justify-content: space-between; font-size: 2.1rem; font-weight: 800; }

        .pos-confirm {
            margin-top: 1rem;
            width: 100%;
            border: 1px solid #66676b;
            border-radius: .95rem;
            background: #35363a;
            color: #ffffff;
            padding: .92rem .8rem;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
        }
        .pos-confirm:hover { background: #3a3b40; }
        .pos-bottom-grid { margin-top: .85rem; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
        .pos-receipt-grid { margin-top: .75rem; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }

        .pos-empty { color: #b7b8bc; font-size: 1rem; }

        .aba-modal-bg {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            padding: 1rem;
        }
        .aba-modal-card {
            width: 100%;
            max-width: 420px;
            border-radius: 1rem;
            background: #e8eaee;
            color: #111827;
            padding: 1.25rem 1rem;
            text-align: center;
        }
        .aba-brand {
            margin: 0 auto .85rem;
            font-size: 2.1rem;
            font-weight: 800;
            letter-spacing: .08em;
            color: #5f7f8c;
        }
        .aba-brand span { color: #35b8de; }
        .aba-khqr-card {
            width: 280px;
            max-width: 100%;
            margin: 0 auto;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,.15);
            background: #fff;
        }
        .aba-khqr-head {
            background: linear-gradient(90deg, #ef5850, #ea463d);
            color: #fff;
            padding: .65rem .9rem;
            font-weight: 700;
            font-size: 1.1rem;
        }
        .aba-khqr-body { padding: .8rem .9rem 1rem; }
        .aba-khqr-merchant { font-size: .95rem; color: #6b7280; }
        .aba-khqr-label { margin-top: .25rem; font-size: .72rem; text-transform: uppercase; letter-spacing: .15em; color: #9ca3af; }
        .aba-khqr-amount { margin-top: .15rem; font-size: 2rem; font-weight: 800; color: #111827; line-height: 1.05; }
        .aba-khqr-ccy { font-size: 1rem; color: #374151; font-weight: 700; margin-left: .3rem; }
        .aba-khqr-rule { border-top: 2px dashed #e5e7eb; margin: .7rem 0 .8rem; }
        .aba-khqr-image-wrap {
            width: 210px;
            height: 210px;
            margin: 0 auto;
            border-radius: .7rem;
            background: #ffffff;
            border: 1px solid #d1d5db;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: .35rem;
        }
        .aba-khqr-image { width: 100%; height: 100%; object-fit: contain; }
        .aba-payway-embed { margin: 0 auto; width: 100%; }
        .aba-payway-iframe {
            width: 100%;
            min-height: 420px;
            border: 0;
            border-radius: .9rem;
            background: #ffffff;
        }
        .aba-note { margin-top: .9rem; font-size: .88rem; color: #6b7280; line-height: 1.35; }
        .aba-status { margin-top: .9rem; font-weight: 700; color: #111827; display: flex; align-items: center; gap: .5rem; }
        .aba-status-pending { color: #f59e0b; }
        .aba-status-approved { color: #10b981; }
        .aba-status-spinner { display: inline-block; width: 1em; height: 1em; border: 2px solid #f59e0b; border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .aba-meta { margin-top: .25rem; font-size: .85rem; color: #6b7280; }
    </style>

    <div class="pos-shell">
    <div class="pos-switch-wrap">
        <label class="pos-switch-label">Quick switch appointment</label>
        <select
            wire:change="selectAppointment($event.target.value)"
            class="pos-switch-select"
        >
            @foreach($this->appointments as $appt)
                <option value="{{ $appt->id }}" @selected($this->appointment_id === $appt->id)>
                    #{{ $appt->id }} · {{ $appt->client?->name }} · {{ $appt->service?->name }} · {{ $appt->appt_date->format('M d, Y') }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="pos-layout">
        <div class="pos-card">
            @if($this->appointment)
                <div class="pos-row">
                    <div class="pos-row-start">
                        <div class="pos-avatar">
                            {{ $this->clientInitials }}
                        </div>
                        <div>
                            <div class="pos-name">{{ $this->appointment->client?->name }}</div>
                            <div class="pos-subtitle">
                                Appt #{{ $this->appointment->id }} · {{ $this->appointment->appt_date->format('M d, Y') }} · {{ \Illuminate\Support\Carbon::parse($this->appointment->start_time)->format('H:i') }}
                            </div>
                        </div>
                    </div>
                    <div class="pos-badge">
                        {{ str($this->appointment->status)->headline() }}
                    </div>
                </div>

                <hr class="pos-divider">

                <div class="pos-title">Services rendered</div>

                <div class="pos-row" style="align-items:flex-start; border-bottom:1px solid #4a4b50; padding-bottom:1rem;">
                    <div>
                        <div class="pos-item-name">{{ $this->appointment->service?->name }}</div>
                        <div class="pos-item-sub">{{ $this->appointment->staff?->name }}</div>
                    </div>
                    <div class="pos-price">${{ number_format($this->subtotal, 2) }}</div>
                </div>

                <div>
                    <div class="pos-tip-title">Add tip</div>
                    <div class="pos-tip-grid">
                        @foreach([0, 10, 15, 20] as $pct)
                            <button
                                type="button"
                                wire:click="setTipPercent({{ $pct }})"
                                class="pos-btn {{ $tip_percent === $pct ? 'active' : '' }}"
                            >
                                {{ $pct === 0 ? 'No tip' : $pct.'%' }}
                            </button>
                        @endforeach

                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            wire:model.live="tip"
                            class="pos-input"
                            placeholder="Custom"
                        />
                    </div>
                </div>
            @else
                <div class="pos-empty">No appointment selected.</div>
            @endif
        </div>

        <div class="pos-card">
            <div class="pos-panel-title">Payment method</div>

            <div class="pos-method-grid">
                @foreach(['cash' => 'Cash', 'card' => 'Card', 'qr_code' => 'ABA Pay & KHQR'] as $val => $label)
                    <button
                        type="button"
                        wire:click="$set('method', '{{ $val }}')"
                        class="pos-btn {{ $method === $val ? 'active' : '' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="pos-summary-title">Summary</div>

            <div>
                <div class="pos-summary-row">
                    <span>Subtotal</span>
                    <span>${{ number_format($this->subtotal, 2) }}</span>
                </div>
                <div class="pos-summary-row">
                    <span>Tip {{ $tip_percent > 0 ? '(' . $tip_percent . '%)' : '' }}</span>
                    <span>${{ number_format($this->tip, 2) }}</span>
                </div>
                <div class="pos-summary-row">
                    <span>Discount</span>
                    <span>-${{ number_format($this->discount + $this->loyaltyDiscount, 2) }}</span>
                </div>
            </div>

            <hr class="pos-divider">

            <div class="pos-total-row">
                <span>Total</span>
                <span>${{ number_format($this->total, 2) }}</span>
            </div>

            <button
                type="button"
                wire:click="processPayment"
                class="pos-confirm"
            >
                Confirm payment ${{ number_format($this->total, 2) }}
            </button>

            <div class="pos-bottom-grid">
                <label class="pos-btn" style="display:flex;align-items:center;justify-content:center;gap:.5rem;">
                    <input type="checkbox" wire:model.live="use_loyalty_points">
                    + Loyalty
                </label>

                <input
                    type="number"
                    min="0"
                    step="0.01"
                    wire:model.live="discount"
                    class="pos-input"
                    placeholder="+ Discount"
                />
            </div>

            @if($use_loyalty_points && $this->appointment)
                <div class="mt-3">
                    <input
                        type="number"
                        min="0"
                        max="{{ $this->appointment->client?->loyalty_points ?? 0 }}"
                        wire:model.live="loyalty_points_to_use"
                        class="pos-input"
                        placeholder="Points to redeem (available: {{ $this->appointment->client?->loyalty_points ?? 0 }})"
                    />
                </div>
            @endif

            <div class="pos-receipt-grid">
                <button type="button" wire:click="printReceipt" class="pos-btn">Print receipt</button>
                <button type="button" wire:click="emailReceipt" class="pos-btn">Email receipt</button>
            </div>
        </div>
    </div>

    @if($showQrPrompt)
        <div
            class="aba-modal-bg"
            wire:click.self="cancelQrPrompt"
        >
            <div class="aba-modal-card">
                <div class="aba-brand">ABA <span>PAY</span></div>

                @php
                    $abaCurrency = $this->payway_payload['currency'] ?? config('services.aba.currency', 'USD');
                    $abaMerchant = trim((string) config('services.aba.merchant_name', 'Salon Payment'));
                @endphp

                @if(!empty($this->payway_payload))
                    <div class="aba-payway-embed" wire:poll.3s="refreshPaywayStatus" wire:ignore>
                        <script>
                            window.__paywayCheckoutInitiated = window.__paywayCheckoutInitiated || {};
                            (function () {
                                const tranId = @js($payway_tran_id);
                                if (!tranId || window.__paywayCheckoutInitiated[tranId]) {
                                    return;
                                }
                                window.__paywayCheckoutInitiated[tranId] = true;
                                
                                // Wait for AbaPayway to be defined
                                const waitForAbaPayway = setInterval(function () {
                                    if (typeof AbaPayway !== 'undefined') {
                                        clearInterval(waitForAbaPayway);
                                        
                                        const payload = @json($this->payway_payload);
                                        
                                        // Callbacks
                                        payload.onSuccess = function (response) {
                                            console.log('PayWay Success:', response);
                                            // Trigger payment verification
                                            if (window.Livewire && window.Livewire.components) {
                                                Livewire.dispatch('payment-success', {tranId: tranId});
                                            }
                                        };
                                        
                                        payload.onError = function (response) {
                                            console.log('PayWay Error:', response);
                                        };
                                        
                                        // Initiate checkout
                                        AbaPayway.checkout(payload);
                                    }
                                }, 100);
                                
                                // Timeout after 5 seconds
                                setTimeout(function () {
                                    clearInterval(waitForAbaPayway);
                                }, 5000);
                            })();
                        </script>
                    </div>
                @elseif($this->abaQrImageUrl || $this->abaTopupTemplateUrl)
                    <div class="aba-khqr-card">
                        <div class="aba-khqr-head">Top up</div>
                        <div class="aba-khqr-body">
                            @if($abaMerchant !== '')
                                <div class="aba-khqr-merchant">{{ $abaMerchant }}</div>
                            @endif
                            <div class="aba-khqr-label">Amount</div>
                            <div class="aba-khqr-amount">
                                ${{ number_format($this->total, 2) }}
                                <span class="aba-khqr-ccy">{{ $abaCurrency }}</span>
                            </div>
                            <div class="aba-khqr-rule"></div>
                            @if($this->abaQrImageUrl)
                                <div class="aba-khqr-image-wrap">
                                    <img src="{{ $this->abaQrImageUrl }}" alt="ABA KHQR" class="aba-khqr-image">
                                </div>
                            @else
                                <img src="{{ $this->abaTopupTemplateUrl }}" alt="ABA topup" style="width:100%;border-radius:.7rem;">
                            @endif
                        </div>
                    </div>
                @endif

                <div class="aba-note">
                    Scan with ABA Mobile or any other mobile banking app that supports KHQR.
                </div>

                @if($payway_tran_id)
                    <div class="aba-status" @class(['aba-status-pending' => $payway_status === 'PENDING', 'aba-status-approved' => $payway_status === 'APPROVED'])>
                        @if($payway_status === 'PENDING')
                            <span class="aba-status-spinner"></span>
                        @endif
                        Status: {{ $payway_status ?? 'PENDING' }}
                    </div>
                    <div class="aba-meta">Transaction ID: {{ $payway_tran_id }}</div>
                @endif

                <div class="pos-bottom-grid" style="margin-top:1rem;">
                    <button type="button" wire:click="refreshPaywayStatus" class="pos-confirm" style="margin-top:0;">
                        Check payment status
                    </button>
                    <button type="button" wire:click="cancelQrPrompt" class="pos-btn">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
    </div>
    <script src="https://checkout.payway.com.kh/plugins/checkout2-0.js" defer></script>
</x-filament-panels::page>
