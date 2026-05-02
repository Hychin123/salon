<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/booking', [BookingController::class, 'create'])->name('booking.create');
Route::get('/booking/availability', [BookingController::class, 'availability'])->name('booking.availability');
Route::post('/booking', [BookingController::class, 'storeOnline'])->name('booking.store');
Route::post('/walk-in-bookings', [BookingController::class, 'storeWalkIn'])->middleware('auth')->name('booking.walkin');

Route::get('/aba/private-image/{path}', function (string $path) {
    $normalizedPath = trim($path, '/');

    abort_unless(Storage::disk('local')->exists('private/' . $normalizedPath), 404);

    return response(Storage::disk('local')->get('private/' . $normalizedPath), 200, [
        'Content-Type' => Storage::disk('local')->mimeType('private/' . $normalizedPath) ?: 'image/jpeg',
        'Cache-Control' => 'no-store, no-cache',
    ]);
})->where('path', '.*')->name('aba.image.private');
