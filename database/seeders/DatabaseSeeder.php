<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\ClassSchedule;
use App\Models\DietPlan;
use App\Models\Equipment;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\PlanAssignment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\Trainer;
use App\Models\User;
use App\Models\WorkoutPlan;
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

        // Seed Trainer Profile + Sample Classes & Upcoming Schedules
        $trainerProfile = Trainer::firstOrCreate(
            ['user_id' => $trainer->id],
            [
                'branch_id' => $mainBranch->id,
                'specialization' => 'HIIT & Functional Strength',
                'certifications' => 'NASM-CPT, CrossFit L1',
                'hourly_rate' => 800.00,
                'is_active' => true,
            ]
        );

        $hiit = GymClass::firstOrCreate(
            ['branch_id' => $mainBranch->id, 'name' => 'HIIT Burn 45'],
            [
                'trainer_id' => $trainerProfile->id,
                'category' => 'HIIT',
                'description' => 'High-intensity interval training for fat loss and conditioning.',
                'default_capacity' => 15,
                'duration_minutes' => 45,
                'is_active' => true,
            ]
        );

        $yoga = GymClass::firstOrCreate(
            ['branch_id' => $mainBranch->id, 'name' => 'Power Flow Yoga'],
            [
                'trainer_id' => $trainerProfile->id,
                'category' => 'Yoga',
                'description' => 'Mobility, core stability, and breathwork recovery flow.',
                'default_capacity' => 20,
                'duration_minutes' => 60,
                'is_active' => true,
            ]
        );

        if (ClassSchedule::count() === 0) {
            ClassSchedule::create([
                'gym_class_id' => $hiit->id,
                'branch_id' => $mainBranch->id,
                'trainer_id' => $trainerProfile->id,
                'room' => 'Studio A',
                'starts_at' => now()->addDay()->setTime(7, 30),
                'ends_at' => now()->addDay()->setTime(8, 15),
                'capacity' => 15,
                'status' => 'scheduled',
            ]);

            ClassSchedule::create([
                'gym_class_id' => $yoga->id,
                'branch_id' => $mainBranch->id,
                'trainer_id' => $trainerProfile->id,
                'room' => 'Mind & Body Room',
                'starts_at' => now()->addDays(2)->setTime(18, 0),
                'ends_at' => now()->addDays(2)->setTime(19, 0),
                'capacity' => 20,
                'status' => 'scheduled',
            ]);
        }

        // Seed Sample Workout & Diet Plans assigned to Demo Member
        if (WorkoutPlan::count() === 0) {
            $wp = WorkoutPlan::create([
                'branch_id' => $mainBranch->id,
                'trainer_id' => $trainerProfile->id,
                'created_by' => $trainer->id,
                'title' => '4-Week Lean Strength Foundation',
                'goal' => 'Muscle Gain & Conditioning',
                'difficulty' => 'intermediate',
                'duration_weeks' => 4,
            ]);
            $wp->items()->createMany([
                ['day_label' => 'Day 1 - Push', 'exercise_name' => 'Barbell Bench Press', 'sets' => 4, 'reps' => '8-10', 'rest_seconds' => 90, 'sort_order' => 1],
                ['day_label' => 'Day 1 - Push', 'exercise_name' => 'Overhead Dumbbell Press', 'sets' => 3, 'reps' => '10-12', 'rest_seconds' => 60, 'sort_order' => 2],
                ['day_label' => 'Day 2 - Pull', 'exercise_name' => 'Lat Pulldown', 'sets' => 4, 'reps' => '10-12', 'rest_seconds' => 60, 'sort_order' => 3],
                ['day_label' => 'Day 3 - Legs', 'exercise_name' => 'Barbell Back Squat', 'sets' => 4, 'reps' => '6-8', 'rest_seconds' => 120, 'sort_order' => 4],
            ]);

            $dp = DietPlan::create([
                'branch_id' => $mainBranch->id,
                'trainer_id' => $trainerProfile->id,
                'created_by' => $trainer->id,
                'title' => 'High-Protein Daily Fuel',
                'goal' => 'Lean Recomposition',
                'daily_calories' => 2200,
                'protein_grams' => 160,
                'carbs_grams' => 230,
                'fat_grams' => 65,
            ]);
            $dp->items()->createMany([
                ['meal_time' => 'Breakfast', 'food_items' => '3 eggs, 1 cup oatmeal, 1 banana', 'calories' => 540, 'protein_grams' => 32, 'sort_order' => 1],
                ['meal_time' => 'Lunch', 'food_items' => '200g grilled chicken breast, 1.5 cups rice, steamed veggies', 'calories' => 680, 'protein_grams' => 55, 'sort_order' => 2],
                ['meal_time' => 'Post-Workout', 'food_items' => '1 scoop whey protein shake + apple', 'calories' => 220, 'protein_grams' => 26, 'sort_order' => 3],
                ['meal_time' => 'Dinner', 'food_items' => '200g bangus/salmon, sweet potato, greens', 'calories' => 640, 'protein_grams' => 45, 'sort_order' => 4],
            ]);

            PlanAssignment::create([
                'member_id' => $memberProfile->id,
                'workout_plan_id' => $wp->id,
                'diet_plan_id' => $dp->id,
                'assigned_by' => $trainer->id,
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addWeeks(4)->toDateString(),
                'status' => 'active',
            ]);
        }

        // Seed Sample Equipment & Retail Products
        if (Equipment::count() === 0) {
            Equipment::create([
                'branch_id' => $mainBranch->id,
                'name' => 'Commercial Treadmill Pro #1',
                'category' => 'Cardio',
                'serial_number' => 'TM-2026-01',
                'condition' => 'good',
                'last_maintained_at' => now()->subDays(14)->toDateString(),
            ]);
            Equipment::create([
                'branch_id' => $mainBranch->id,
                'name' => 'Power Rack & Olympic Barbell Set',
                'category' => 'Free Weights',
                'serial_number' => 'PR-2026-01',
                'condition' => 'good',
            ]);
        }

        if (Product::count() === 0) {
            Product::create([
                'branch_id' => $mainBranch->id,
                'name' => 'Whey Protein Single Shake',
                'sku' => 'SUP-WHEY-01',
                'category' => 'Supplements',
                'price' => 120.00,
                'cost' => 65.00,
                'stock_quantity' => 25,
                'low_stock_threshold' => 5,
            ]);
            Product::create([
                'branch_id' => $mainBranch->id,
                'name' => 'Electrolyte Sports Drink 500ml',
                'sku' => 'BEV-ELEC-01',
                'category' => 'Beverages',
                'price' => 60.00,
                'cost' => 30.00,
                'stock_quantity' => 40,
                'low_stock_threshold' => 10,
            ]);
        }

        if (Attendance::count() === 0) {
            Attendance::create([
                'branch_id' => $mainBranch->id,
                'member_id' => $memberProfile->id,
                'checked_in_at' => now()->subHours(2),
                'checked_out_at' => now()->subHour(),
                'method' => 'qr',
                'status' => 'allowed',
                'checked_in_by' => $staff->id,
            ]);
        }
    }
}
