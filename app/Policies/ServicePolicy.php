<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Service;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServicePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_service');
    }

    public function view(AuthUser $authUser, Service $service): bool
    {
        return $authUser->can('view_service');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_service');
    }

    public function update(AuthUser $authUser, Service $service): bool
    {
        return $authUser->can('update_service');
    }

    public function delete(AuthUser $authUser, Service $service): bool
    {
        return $authUser->can('delete_service');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_service');
    }

    public function restore(AuthUser $authUser, Service $service): bool
    {
        return $authUser->can('restore_service');
    }

    public function forceDelete(AuthUser $authUser, Service $service): bool
    {
        return $authUser->can('force_delete_service');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_service');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_service');
    }

    public function replicate(AuthUser $authUser, Service $service): bool
    {
        return $authUser->can('replicate_service');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_service');
    }

}
