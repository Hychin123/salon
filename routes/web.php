<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use App\Services\PaywayService;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/booking', [BookingController::class, 'create'])->name('booking.create');
Route::get('/booking/availability', [BookingController::class, 'availability'])->name('booking.availability');
Route::post('/booking', [BookingController::class, 'storeOnline'])->name('booking.store');
Route::post('/walk-in-bookings', [BookingController::class, 'storeWalkIn'])->middleware('auth')->name('booking.walkin');

Route::get('/payway/return', function () {
    return response('Payment completed. You can close this window.', 200, [
        'Content-Type' => 'text/plain',
        'Cache-Control' => 'no-store, no-cache',
    ]);
})->name('payway.return');

Route::get('/aba/private-image/{path}', function (string $path) {
    $normalizedPath = trim($path, '/');

    abort_unless(Storage::disk('local')->exists('private/' . $normalizedPath), 404);

    return response(Storage::disk('local')->get('private/' . $normalizedPath), 200, [
        'Content-Type' => Storage::disk('local')->mimeType('private/' . $normalizedPath) ?: 'image/jpeg',
        'Cache-Control' => 'no-store, no-cache',
    ]);
})->where('path', '.*')->name('aba.image.private');

Route::get('/payway-debug', function () {
    $service = new PaywayService();

    // -----------------------------------------------------------------------
    // Simulate EXACTLY what your controller sends to buildPurchasePayload()
    // Replace these values with your actual test values
    // -----------------------------------------------------------------------
    $params = [
        'req_time'       => now()->format('YmdHis'),
        'merchant_id'    => config('services.payway.merchant_id'),
        'tran_id'        => 'TEST' . now()->format('YmdHis'),
        'amount'         => '45.00',
        'items'          => [
            [
                'name'     => 'Hair Coloring',
                'quantity' => 1,
                'price'    => '45.00',
            ],
        ],
        'shipping'       => '0',
        'status_url'     => config('app.url') . '/payway/status',
        'return_url'     => config('app.url') . '/payway/return',
        // 'cancel_url'  => config('app.url') . '/payway/cancel',  // add if you send it
        // 'type'        => 'purchase',                            // add if you send it
        // 'payment_option' => 'abapay',                          // add if you send it
    ];

    // -----------------------------------------------------------------------
    // Step-by-step hash reproduction — mirrors buildPurchasePayload() exactly
    // -----------------------------------------------------------------------

    // 1. Format amount
    $params['amount'] = number_format((float) $params['amount'], 2, '.', '');

    // 2. Encode items
    $itemsJson   = json_encode($params['items']);
    $itemsBase64 = base64_encode($itemsJson);
    $params['items'] = $itemsBase64;

    // 3. Hash fields (must match DEFAULT_PURCHASE_HASH_FIELDS exactly)
    $hashFields = [
        'req_time',
        'merchant_id',
        'tran_id',
        'amount',
        'items',
        'shipping',
        'status_url',
        'return_url',
    ];

    // 4. Build ordered values for hash
    $orderedValues = [];
    foreach ($hashFields as $field) {
        if (array_key_exists($field, $params)) {
            $orderedValues[$field] = $params[$field];
        }
    }

    // 5. Concatenate
    $b4hash = implode('', array_values($orderedValues));

    // 6. Generate hash
    $secret = config('services.payway.api_key');
    $hash   = base64_encode(hash_hmac('sha512', $b4hash, $secret, true));

    // -----------------------------------------------------------------------
    // Output everything for inspection
    // -----------------------------------------------------------------------
    return response()->json([
        'debug' => [
            'hash_fields_used'   => $hashFields,
            'ordered_values'     => $orderedValues,
            'items_json'         => $itemsJson,
            'items_base64'       => $itemsBase64,
            'b4hash_string'      => $b4hash,
            'b4hash_length'      => strlen($b4hash),
            'generated_hash'     => $hash,
            'merchant_id_raw'    => config('services.payway.merchant_id'),
            'merchant_id_length' => strlen(config('services.payway.merchant_id') ?? ''),
            'api_key_length'     => strlen(config('services.payway.api_key') ?? ''),
        ],
        'full_payload' => array_merge($params, ['hash' => $hash]),
    ]);
});

Route::get('/debug/payway-hash', function () {
    $service = app(\App\Services\PaywayService::class);
    $payload = [
        'req_time' => '20260505090000',
        'merchant_id' => config('services.payway.merchant_id'),
        'tran_id' => 'TEST123',
        'amount' => '45.00',
        'items' => base64_encode(json_encode([["name" => "Test", "quantity" => 1, "price" => "45.00"]])),
        'shipping' => '0.00',
        'tax' => '0.00',
        'discount' => '0.00',
        'currency' => 'USD',
        'return_url' => 'http://localhost/return',
        'cancel_url' => 'http://localhost/cancel',
        'status_url' => 'http://localhost/status',
    ];
    $hashFields = ['req_time', 'merchant_id', 'tran_id', 'amount', 'items', 'shipping', 'tax', 'discount', 'currency', 'return_url', 'cancel_url', 'status_url'];

    $b4hash = '';
    foreach ($hashFields as $field) {
        if (isset($payload[$field])) {
            $b4hash .= $payload[$field];
        }
    }

    return [
        'api_key' => substr(config('services.payway.api_key'), 0, 10) . '...',
        'api_key_length' => strlen(config('services.payway.api_key')),
        'b4hash_string' => $b4hash,
        'generated_hash' => $service->generateHash($payload, $hashFields),
        'merchant_id' => config('services.payway.merchant_id'),
    ];
});
