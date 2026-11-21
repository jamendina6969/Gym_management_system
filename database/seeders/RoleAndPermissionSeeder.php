<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Branches
            'branches.view',
            'branches.manage',

            // Members
            'members.view',
            'members.create',
            'members.edit',
            'members.delete',
            'members.approve',

            // Plans & Subscriptions
            'plans.view',
            'plans.manage',
            'subscriptions.view',
            'subscriptions.manage',

            // Payments
            'payments.view',
            'payments.record',
            'payments.verify',
            'payments.refund',

            // Attendance / Check-in
            'attendance.view',
            'attendance.checkin',

            // Trainers & Classes
            'trainers.view',
            'trainers.manage',
            'classes.view',
            'classes.manage',
            'bookings.manage',

            // Workout & Diet Plans
            'workouts.view',
            'workouts.manage',
            'diets.view',
            'diets.manage',

            // Equipment & Inventory
            'equipment.view',
            'equipment.manage',
            'inventory.view',
            'inventory.manage',

            // Reports & Settings & Staff
            'reports.view',
            'reports.export',
            'settings.manage',
            'staff.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // 1. Super Admin (also granted all via Gate::before)
        $superAdmin = Role::findOrCreate('super-admin', 'web');
        $superAdmin->syncPermissions($permissions);

        // 2. Branch Manager (full control within their assigned branch)
        $branchManager = Role::findOrCreate('branch-manager', 'web');
        $branchManager->syncPermissions([
            'branches.view',
            'members.view', 'members.create', 'members.edit', 'members.delete', 'members.approve',
            'plans.view', 'plans.manage',
            'subscriptions.view', 'subscriptions.manage',
            'payments.view', 'payments.record', 'payments.verify', 'payments.refund',
            'attendance.view', 'attendance.checkin',
            'trainers.view', 'trainers.manage',
            'classes.view', 'classes.manage', 'bookings.manage',
            'workouts.view', 'workouts.manage',
            'diets.view', 'diets.manage',
            'equipment.view', 'equipment.manage',
            'inventory.view', 'inventory.manage',
            'reports.view', 'reports.export',
            'settings.manage',
            'staff.manage',
        ]);

        // 3. Staff / Receptionist
        $staff = Role::findOrCreate('staff', 'web');
        $staff->syncPermissions([
            'members.view', 'members.create', 'members.edit', 'members.approve',
            'plans.view',
            'subscriptions.view', 'subscriptions.manage',
            'payments.view', 'payments.record', 'payments.verify',
            'attendance.view', 'attendance.checkin',
            'trainers.view',
            'classes.view', 'bookings.manage',
            'equipment.view',
            'inventory.view', 'inventory.manage',
        ]);

        // 4. Trainer
        $trainer = Role::findOrCreate('trainer', 'web');
        $trainer->syncPermissions([
            'members.view',
            'attendance.view',
            'classes.view',
            'workouts.view', 'workouts.manage',
            'diets.view', 'diets.manage',
        ]);

        // 5. Member (portal access)
        Role::findOrCreate('member', 'web');
    }
}
