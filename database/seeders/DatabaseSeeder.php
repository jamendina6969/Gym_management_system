<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Subscription;
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

        $memberUser = User::firstOrCreate(
            ['email' => 'member@ironpulse.gym'],
            [
                'name' => 'Juan Dela Cruz',
                'phone' => '+63 917 000 0005',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $memberUser->syncRoles(['member']);

        // Default Membership Plans
        $monthlyPlan = MembershipPlan::firstOrCreate(
            ['slug' => 'monthly-standard'],
            [
                'branch_id' => $mainBranch->id,
                'name' => 'Monthly Standard',
                'description' => 'Full gym floor access at your home branch plus 8 group classes per month.',
                'billing_cycle' => 'monthly',
                'duration_days' => 30,
                'price' => 1500.00,
                'class_credits' => 8,
                'access_scope' => 'single_branch',
                'is_active' => true,
            ]
        );

        MembershipPlan::firstOrCreate(
            ['slug' => 'quarterly-saver'],
            [
                'branch_id' => $mainBranch->id,
                'name' => 'Quarterly Saver',
                'description' => '3-month commitment at a discounted rate with 30 class credits.',
                'billing_cycle' => 'quarterly',
                'duration_days' => 90,
                'price' => 4000.00,
                'class_credits' => 30,
                'access_scope' => 'single_branch',
                'is_active' => true,
            ]
        );

        MembershipPlan::firstOrCreate(
            ['slug' => 'all-access-vip-monthly'],
            [
                'branch_id' => null,
                'name' => 'All-Access VIP Monthly',
                'description' => 'Unlimited access to all IronPulse branches and unlimited group classes.',
                'billing_cycle' => 'monthly',
                'duration_days' => 30,
                'price' => 2500.00,
                'class_credits' => null,
                'access_scope' => 'all_access',
                'is_active' => true,
            ]
        );

        // Seed Member Profile + Active Subscription + Paid Invoice for Demo Member
        $memberProfile = Member::firstOrCreate(
            ['user_id' => $memberUser->id],
            [
                'home_branch_id' => $mainBranch->id,
                'waiver_accepted_at' => now(),
                'status' => 'active',
                'approved_at' => now(),
                'approved_by' => $superAdmin->id,
            ]
        );

        if ($memberProfile->subscriptions()->count() === 0) {
            $sub = Subscription::create([
                'member_id' => $memberProfile->id,
                'membership_plan_id' => $monthlyPlan->id,
                'branch_id' => $mainBranch->id,
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addDays(30)->toDateString(),
                'price' => $monthlyPlan->price,
                'amount_paid' => 0,
                'remaining_class_credits' => $monthlyPlan->class_credits,
                'status' => 'active',
                'created_by' => $staff->id,
            ]);

            $payment = Payment::create([
                'branch_id' => $mainBranch->id,
                'member_id' => $memberProfile->id,
                'subscription_id' => $sub->id,
                'amount' => $monthlyPlan->price,
                'payment_method' => 'gcash',
                'gateway' => 'manual',
                'reference_number' => 'GCASH-DEMO-001',
                'status' => 'paid',
                'paid_at' => now(),
                'recorded_by' => $staff->id,
                'verified_by' => $staff->id,
            ]);

            $payment->markAsPaid($staff);
        }
    }
}
