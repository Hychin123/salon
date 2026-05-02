<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'notes',
        'allergy_notes',
        'health_notes',
        'loyalty_points',
    ];

    protected $casts = [
        'loyalty_points' => 'integer',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function preferredServices(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'client_service_preferences')
            ->withTimestamps();
    }
}
