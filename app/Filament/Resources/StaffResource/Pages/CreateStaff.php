<?php

namespace App\Filament\Resources\StaffResource\Pages;

use App\Filament\Resources\StaffResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use BezhanSalleh\FilamentShield\Support\Utils;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $email = $data['user_email'] ?? null;
        $password = $data['user_password'] ?? null;
        $role = $data['role'] ?? null;

        unset($data['user_email'], $data['user_password'], $data['user_password_confirmation']);

        $user = User::create([
            'name' => $data['name'],
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $roles = array_filter([
            Utils::getPanelUserRoleName(),
            $role,
        ]);

        $user->syncRoles($roles);

        $data['user_id'] = $user->id;

        return $data;
    }
}
