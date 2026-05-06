<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Appointment;
use Illuminate\Auth\Access\HandlesAuthorization;

class AppointmentPolicy
{
    use HandlesAuthorization;

    // Super admin & manager bypass via Shield — handled automatically
    public function viewAny(AuthUser $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'manager', 'receptionist', 'therapist', 'stylist']);
    }

    public function view(AuthUser $user, Appointment $appointment): bool
    {
        if ($user->hasAnyRole(['super_admin', 'manager', 'receptionist'])) {
            return true;
        }
        // Therapist/stylist: only their own appointments (linked via staff.user_id)
        return $user->hasAnyRole(['therapist', 'stylist'])
            && $appointment->staff->user_id === $user->id;
    }

    public function create(AuthUser $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'manager', 'receptionist']);
    }

    public function update(AuthUser $user, Appointment $appointment): bool
    {
        return $user->hasAnyRole(['super_admin', 'manager', 'receptionist']);
    }

    public function delete(AuthUser $user, Appointment $appointment): bool
    {
        return $user->hasAnyRole(['super_admin', 'manager']);
    }

    public function deleteAny(AuthUser $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'manager']);
    }

    public function restore(AuthUser $user, Appointment $appointment): bool
    {
        return $user->hasAnyRole(['super_admin', 'manager']);
    }

    public function forceDelete(AuthUser $user, Appointment $appointment): bool
    {
        return $user->hasAnyRole(['super_admin', 'manager']);
    }

    public function forceDeleteAny(AuthUser $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'manager']);
    }

    public function restoreAny(AuthUser $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'manager']);
    }

    public function replicate(AuthUser $user, Appointment $appointment): bool
    {
        return $user->hasAnyRole(['super_admin', 'manager']);
    }

    public function reorder(AuthUser $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'manager']);
    }

}
