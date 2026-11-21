<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        $mainBranch = Branch::firstOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'Main Branch',
                'address' => '123 Ayala Avenue, Makati City, Metro Manila',
                'phone' => '+63 2 8123 4567',
                'email' => 'main@ironpulse.gym',
                'opening_hours' => [
                    'mon_fri' => '06:00 - 22:00',
                    'sat' => '07:00 - 20:00',
                    'sun' => '08:00 - 18:00',
                ],
                'allow_self_registration' => true,
                'is_active' => true,
            ]
        );

        // Global default settings
        Setting::setValue('gym_name', 'IronPulse Gym', null, 'general');
        Setting::setValue('currency', 'PHP', null, 'general');
        Setting::setValue('currency_symbol', '₱', null, 'general');
        Setting::setValue('timezone', 'Asia/Manila', null, 'general');
        Setting::setValue('expiry_reminder_days', '7', null, 'notifications');

        // Default accounts (dev/initial setup)
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@ironpulse.gym'],
            [
                'name' => 'Super Admin',
                'phone' => '+63 917 000 0001',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $superAdmin->syncRoles(['super-admin']);

        $manager = User::firstOrCreate(
            ['email' => 'manager@ironpulse.gym'],
            [
                'name' => 'Branch Manager',
                'phone' => '+63 917 000 0002',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $manager->syncRoles(['branch-manager']);
        $manager->branches()->syncWithoutDetaching([$mainBranch->id => ['is_primary' => true]]);

        $staff = User::firstOrCreate(
            ['email' => 'staff@ironpulse.gym'],
            [
                'name' => 'Front Desk Staff',
                'phone' => '+63 917 000 0003',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $staff->syncRoles(['staff']);
        $staff->branches()->syncWithoutDetaching([$mainBranch->id => ['is_primary' => true]]);

        $trainer = User::firstOrCreate(
            ['email' => 'trainer@ironpulse.gym'],
            [
                'name' => 'Coach Alex',
                'phone' => '+63 917 000 0004',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $trainer->syncRoles(['trainer']);
        $trainer->branches()->syncWithoutDetaching([$mainBranch->id => ['is_primary' => true]]);

        $member = User::firstOrCreate(
            ['email' => 'member@ironpulse.gym'],
            [
                'name' => 'Juan Dela Cruz',
                'phone' => '+63 917 000 0005',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $member->syncRoles(['member']);
    }
}
