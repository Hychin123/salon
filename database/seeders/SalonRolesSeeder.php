<?php
// database/seeders/SalonRolesSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SalonRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $guardName = config('auth.defaults.guard');

        $managerPermissions = [
            // Appointments
            'view_appointment',
            'view_any_appointment',
            'create_appointment',
            'update_appointment',
            'delete_appointment',
            // Clients
            'view_client',
            'view_any_client',
            'create_client',
            'update_client',
            'delete_client',
            // Staff
            'view_staff',
            'view_any_staff',
            'create_staff',
            'update_staff',
            // Services
            'view_service',
            'view_any_service',
            'create_service',
            'update_service',
            'delete_service',
            // Payments / POS
            'view_payment',
            'view_any_payment',
            'create_payment',
            // Pages
            'page_Checkout',
        ];

        $receptionistPermissions = [
            'view_appointment',
            'view_any_appointment',
            'create_appointment',
            'update_appointment',
            'view_client',
            'view_any_client',
            'create_client',
            'update_client',
            'view_service',
            'view_any_service',
            'view_payment',
            'create_payment',
            'page_Checkout',
        ];

        $therapistPermissions = [
            'view_appointment',     // scoped to own in policy
            'view_any_appointment', // scoped to own in policy
            'view_service',
            'view_any_service',
            'view_staff',           // own profile only — via policy
        ];

        $stylistPermissions = $therapistPermissions;

        $roomPermissions = [
            'view_room',
            'view_any_room',
            'create_room',
            'update_room',
            'delete_room',
        ];

        $rolePermissions = [
            'view_role',
            'view_any_role',
            'create_role',
            'update_role',
            'delete_role',
            'delete_any_role',
            'restore_role',
            'restore_any_role',
            'force_delete_role',
            'force_delete_any_role',
            'replicate_role',
            'reorder_role',
        ];

        $allPermissions = array_unique(array_merge(
            $managerPermissions,
            $receptionistPermissions,
            $therapistPermissions,
            $stylistPermissions,
            $roomPermissions,
            $rolePermissions,
        ));

        foreach ($allPermissions as $permission) {
            Permission::findOrCreate($permission, $guardName);
        }

        // ── 1. SUPER ADMIN ─────────────────────────────────────
        // Keep super_admin fully privileged by assigning all permissions.
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->syncPermissions($allPermissions);

        // Create super admin user
        $superAdminUser = User::firstOrCreate(
            ['email' => 'admin@salon.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin@123'),
            ]
        );
        $superAdminUser->assignRole('super_admin');

        // ── 2. MANAGER ─────────────────────────────────────────
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions($managerPermissions);

        // ── 3. RECEPTIONIST ────────────────────────────────────
        $receptionist = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist->syncPermissions($receptionistPermissions);

        // ── 4. THERAPIST ───────────────────────────────────────
        $therapist = Role::firstOrCreate(['name' => 'therapist']);
        $therapist->syncPermissions($therapistPermissions);

        // ── 5. STYLIST ─────────────────────────────────────────
        $stylist = Role::firstOrCreate(['name' => 'stylist']);
        $stylist->syncPermissions($stylistPermissions);

        $this->command->info('Salon roles and super admin user seeded.');
    }
}
