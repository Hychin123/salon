<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Staff;
use Illuminate\Auth\Access\HandlesAuthorization;

class StaffPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_staff');
    }

    public function view(AuthUser $authUser, Staff $staff): bool
    {
        return $authUser->can('view_staff');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_staff');
    }

    public function update(AuthUser $authUser, Staff $staff): bool
    {
        return $authUser->can('update_staff');
    }

    public function delete(AuthUser $authUser, Staff $staff): bool
    {
        return $authUser->can('delete_staff');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_staff');
    }

    public function restore(AuthUser $authUser, Staff $staff): bool
    {
        return $authUser->can('restore_staff');
    }

    public function forceDelete(AuthUser $authUser, Staff $staff): bool
    {
        return $authUser->can('force_delete_staff');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_staff');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_staff');
    }

    public function replicate(AuthUser $authUser, Staff $staff): bool
    {
        return $authUser->can('replicate_staff');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_staff');
    }

}
