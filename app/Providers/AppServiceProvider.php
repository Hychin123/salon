<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Appointment;
use App\Policies\AppointmentPolicy;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        Gate::policy(Appointment::class, AppointmentPolicy::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
