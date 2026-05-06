<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Room;
use Illuminate\Auth\Access\HandlesAuthorization;

class RoomPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_room');
    }

    public function view(AuthUser $authUser, Room $room): bool
    {
        return $authUser->can('view_room');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_room');
    }

    public function update(AuthUser $authUser, Room $room): bool
    {
        return $authUser->can('update_room');
    }

    public function delete(AuthUser $authUser, Room $room): bool
    {
        return $authUser->can('delete_room');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_room');
    }

    public function restore(AuthUser $authUser, Room $room): bool
    {
        return $authUser->can('restore_room');
    }

    public function forceDelete(AuthUser $authUser, Room $room): bool
    {
        return $authUser->can('force_delete_room');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_room');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_room');
    }

    public function replicate(AuthUser $authUser, Room $room): bool
    {
        return $authUser->can('replicate_room');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_room');
    }

}
