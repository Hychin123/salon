<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaywayService
{
    public function purchaseUrl(): string
    {
        return $this->baseUrl() . '/api/payment-gateway/v1/payments/purchase';
    }

    public function checkUrl(): string
    {
        return $this->baseUrl() . '/api/payment-gateway/v1/payments/check-transaction-2';
    }

    public function buildPurchasePayload(array $params): array
    {
        $payload = $this->stripEmpty($params);
        $payload['hash'] = $this->generateHash($payload);

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

        $payload['hash'] = $this->generateHash($payload);

        $response = Http::asJson()
            ->timeout(20)
            ->retry(1, 200)
            ->post($this->checkUrl(), $payload);

        if (! $response->ok()) {
            throw new RuntimeException('PayWay check-transaction failed with status ' . $response->status());
        }

        return $response->json();
    }

    public function generateHash(array $params): string
    {
        $secret = $this->apiKey();

        ksort($params);

        $b4hash = '';
        foreach ($params as $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $b4hash .= $value;
        }

        return base64_encode(hash_hmac('sha512', $b4hash, $secret, true));
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

        if ($merchantId === '') {
            throw new RuntimeException('PAYWAY_MERCHANT_ID is not configured.');
        }

        return $merchantId;
    }

    private function apiKey(): string
    {
        $apiKey = trim((string) config('services.payway.api_key'));

        if ($apiKey === '') {
            throw new RuntimeException('PAYWAY_API_KEY is not configured.');
        }

        return $apiKey;
    }
}
