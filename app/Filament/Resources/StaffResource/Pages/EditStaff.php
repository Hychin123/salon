<?php

namespace App\Filament\Resources\StaffResource\Pages;

use App\Filament\Resources\StaffResource;
use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditStaff extends EditRecord
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = $this->getRecord()->user;

        $data['user_email'] = $user?->email;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $email = $data['user_email'] ?? null;
        $password = $data['user_password'] ?? null;
        $role = $data['role'] ?? null;

        unset($data['user_email'], $data['user_password'], $data['user_password_confirmation']);

        $user = $this->getRecord()->user;

        if (! $user && filled($email)) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $email,
                'password' => Hash::make($password),
            ]);
        }

        if ($user) {
            $user->name = $data['name'];
            $user->email = $email;

            if (filled($password)) {
                $user->password = Hash::make($password);
            }

            $user->save();

            $roles = array_filter([
                Utils::getPanelUserRoleName(),
                $role,
            ]);

            $user->syncRoles($roles);

            $data['user_id'] = $user->id;
        }

        return $data;
    }
}
