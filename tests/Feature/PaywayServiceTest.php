<?php

namespace Tests\Feature;

use App\Services\PaywayService;
use Tests\TestCase;

class PaywayServiceTest extends TestCase
{
    public function test_purchase_hash_uses_payway_field_order(): void
    {
        config()->set('services.payway.api_key', 'test-secret');

        $service = app(PaywayService::class);

        $payload = $service->buildPurchasePayload([
            'amount' => '45.00',
            'tran_id' => '2605031630320009',
            'merchant_id' => 'ec475225',
            'return_url' => 'https://example.com/return',
            'payment_option' => 'abapay_khqr',
            'currency' => 'USD',
            'req_time' => '20260504142000',
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'test@example.com',
            'phone' => '012345678',
            'type' => 'purchase',
            'return_params' => 'appointment_id=9',
        ]);

        $expectedBase = implode('', [
            '20260504142000',
            'ec475225',
            '2605031630320009',
            '45.00',
            'USD',
            'https://example.com/return',
        ]);
        $expectedHash = base64_encode(hash_hmac('sha512', $expectedBase, 'test-secret', true));

        $this->assertSame($expectedHash, $payload['hash']);
    }

    public function test_generate_hash_can_follow_explicit_order(): void
    {
        config()->set('services.payway.api_key', 'test-secret');

        $service = app(PaywayService::class);
        $hash = $service->generateHash(
            [
                'merchant_id' => 'ec475225',
                'tran_id' => '2605031630320009',
                'req_time' => '20260504142000',
            ],
            ['req_time', 'merchant_id', 'tran_id']
        );

        $expectedBase = '20260504142000ec4752252605031630320009';
        $expectedHash = base64_encode(hash_hmac('sha512', $expectedBase, 'test-secret', true));

        $this->assertSame($expectedHash, $hash);
    }
}
