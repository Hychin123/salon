<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaywayService
{
    /**
     * PayWay purchase hash field order (v3 default).
     * Override with PAYWAY_HASH_FIELDS if your account requires a different order.
     *
     * @var array<int, string>
     */
    private const DEFAULT_PURCHASE_HASH_FIELDS = [
        'req_time',
        'merchant_id',
        'tran_id',
        'amount',
        'items',
        'shipping',
        'tax',
        'discount',
        'currency',
        'return_url',
        'cancel_url',
        'status_url',
    ];

    public function purchaseUrl(): string
    {
        return $this->baseUrl() . '/api/payment-gateway/v1/payments/purchase';
    }

    public function checkUrl(): string
    {
        return $this->baseUrl() . '/api/payment-gateway/v1/payments/check-transaction-2';
    }

    public function checkoutScriptUrl(): string
    {
        $override = trim((string) config('services.payway.checkout_script_url'));
        if ($override !== '') {
            return $override;
        }

        return 'https://checkout.payway.com.kh/plugins/checkout2-0.js';
    }

    public function buildPurchasePayload(array $params): array
    {
        if (isset($params['amount'])) {
            $params['amount'] = $this->formatMoney($params['amount']);
        }
        foreach (['shipping', 'tax', 'discount'] as $field) {
            if (isset($params[$field])) {
                $params[$field] = $this->formatMoney($params[$field]);
            }
        }
        if (isset($params['items']) && is_array($params['items'])) {
            $params['items'] = base64_encode(json_encode($params['items']));
        }

        $hashFields = $this->purchaseHashFields();
        $payload = $this->normalizePayloadOrder($params, $hashFields);
        $payload = $this->stripEmpty($payload);
        $payload['hash'] = $this->generateHash($payload, $hashFields);

        return $payload;
    }

    public function checkTransaction(string $tranId): array
    {
        $reqTime = now()->format('YmdHis');

        $payload = [
            'req_time' => $reqTime,
            'merchant_id' => $this->merchantId(),
            'tran_id' => $tranId,
        ];

        $payload['hash'] = $this->generateHash($payload, ['req_time', 'merchant_id', 'tran_id']);

        $response = Http::asJson()
            ->timeout(20)
            ->retry(1, 200)
            ->post($this->checkUrl(), $payload);

        if (! $response->ok()) {
            throw new RuntimeException('PayWay check-transaction failed with status ' . $response->status());
        }

        return $response->json();
    }

    public function generateHash(array $params, ?array $orderedFields = null): string
    {
        $secret = $this->apiKey();
        $params = $this->stripEmpty($params);
        $orderedValues = $this->orderedValues($params, $orderedFields);

        $b4hash = '';
        foreach ($orderedValues as $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $b4hash .= $value;
        }

        return base64_encode(hash_hmac('sha512', $b4hash, $secret, true));
    }

    private function purchaseHashFields(): array
    {
        $configured = config('services.payway.hash_fields');

        if (is_string($configured)) {
            $fields = array_values(array_filter(array_map('trim', explode(',', $configured))));
            if ($fields !== []) {
                return $fields;
            }
        }

        if (is_array($configured) && $configured !== []) {
            return array_values($configured);
        }

        return self::DEFAULT_PURCHASE_HASH_FIELDS;
    }

    private function stripEmpty(array $params): array
    {
        $clean = [];

        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    private function normalizePayloadOrder(array $params, array $orderedFields): array
    {
        $ordered = [];

        foreach ($orderedFields as $field) {
            if (! array_key_exists($field, $params)) {
                continue;
            }

            $ordered[$field] = $params[$field];
            unset($params[$field]);
        }

        foreach ($params as $field => $value) {
            $ordered[$field] = $value;
        }

        return $ordered;
    }

    private function orderedValues(array $params, ?array $orderedFields = null): array
    {
        if ($orderedFields === null) {
            return array_values($params);
        }

        $orderedValues = [];
        foreach ($orderedFields as $field) {
            if (! array_key_exists($field, $params)) {
                continue;
            }

            $orderedValues[] = $params[$field];
        }

        return $orderedValues;
    }

    private function baseUrl(): string
    {
        $base = trim((string) config('services.payway.base_url'));

        if ($base === '') {
            throw new RuntimeException('PAYWAY_BASE_URL is not configured.');
        }

        return rtrim($base, '/');
    }

    private function merchantId(): string
    {
        $merchantId = trim((string) config('services.payway.merchant_id'));
        $merchantId = preg_replace('/\s+/', '', $merchantId) ?? '';

        if ($merchantId === '') {
            throw new RuntimeException('PAYWAY_MERCHANT_ID is not configured.');
        }

        return $merchantId;
    }

    private function apiKey(): string
    {
        $apiKey = trim((string) config('services.payway.api_key'));
        $apiKey = preg_replace('/\s+/', '', $apiKey) ?? '';

        if ($apiKey === '') {
            throw new RuntimeException('PAYWAY_API_KEY is not configured.');
        }

        return $apiKey;
    }

    private function formatMoney(string|int|float $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
