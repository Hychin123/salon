<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'appointment_id',
        'amount',
        'tip',
        'method',
        'payway_tran_id',
        'payway_status',
        'payway_apv',
        'payway_requested_at',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tip' => 'decimal:2',
        'payway_requested_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
