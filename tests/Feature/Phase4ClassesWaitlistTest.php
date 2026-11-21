<?php

use App\Livewire\Admin\Classes\ClassManager;
use App\Livewire\Portal\ClassBrowser;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\ClassSchedule;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Subscription;
use App\Models\Trainer;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin can create trainer, class type, and schedule session via Livewire', function () {
    $branch = Branch::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    Livewire::actingAs($admin)
        ->test(ClassManager::class)
        ->call('openTrainerModal')
        ->set('trainer_name', 'Coach Mia')
        ->set('trainer_email', 'mia@ironpulse.gym')
        ->set('trainer_branch_id', $branch->id)
        ->set('trainer_specialization', 'Boxing')
        ->call('saveTrainer')
        ->assertHasNoErrors();

    $trainer = Trainer::firstOrFail();
    expect($trainer->specialization)->toBe('Boxing');

    Livewire::actingAs($admin)
        ->test(ClassManager::class)
        ->call('openClassModal')
        ->set('class_name', 'Boxing Basics')
        ->set('class_category', 'Boxing')
        ->set('class_branch_id', $branch->id)
        ->set('class_trainer_id', $trainer->id)
        ->set('class_capacity', 10)
        ->set('class_duration', 60)
        ->call('saveClass')
        ->assertHasNoErrors();

    $gymClass = GymClass::where('name', 'Boxing Basics')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ClassManager::class)
        ->call('openScheduleModal')
        ->set('schedule_class_id', $gymClass->id)
        ->set('schedule_date', now()->addDays(2)->toDateString())
        ->set('schedule_time', '09:00')
        ->set('schedule_capacity', 10)
        ->call('saveSchedule')
        ->assertHasNoErrors();

    expect(ClassSchedule::where('gym_class_id', $gymClass->id)->count())->toBe(1);
});

test('class booking enforces capacity, waitlists overflow, and auto-promotes on cancel with credit adjustments', function () {
    $branch = Branch::factory()->create();
    $plan = MembershipPlan::create([
        'branch_id' => $branch->id,
        'name' => 'Monthly 5 Classes',
        'billing_cycle' => 'monthly',
        'duration_days' => 30,
        'price' => 1500,
        'class_credits' => 5,
        'access_scope' => 'single_branch',
    ]);

    $gymClass = GymClass::create([
        'branch_id' => $branch->id,
        'name' => 'Small Group Lifting',
        'default_capacity' => 1,
        'duration_minutes' => 60,
    ]);

    $schedule = ClassSchedule::create([
        'gym_class_id' => $gymClass->id,
        'branch_id' => $branch->id,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHour(),
        'capacity' => 1,
        'status' => 'scheduled',
    ]);

    // Member 1
    $user1 = User::factory()->create();
    $user1->assignRole('member');
    $member1 = Member::create(['user_id' => $user1->id, 'home_branch_id' => $branch->id, 'status' => 'active']);
    $sub1 = Subscription::create([
        'member_id' => $member1->id,
        'membership_plan_id' => $plan->id,
        'branch_id' => $branch->id,
        'starts_at' => now()->toDateString(),
        'ends_at' => now()->addDays(30)->toDateString(),
        'price' => 1500,
        'amount_paid' => 1500,
        'remaining_class_credits' => 5,
        'status' => 'active',
    ]);

    // Member 2
    $user2 = User::factory()->create();
    $user2->assignRole('member');
    $member2 = Member::create(['user_id' => $user2->id, 'home_branch_id' => $branch->id, 'status' => 'active']);
    $sub2 = Subscription::create([
        'member_id' => $member2->id,
        'membership_plan_id' => $plan->id,
        'branch_id' => $branch->id,
        'starts_at' => now()->toDateString(),
        'ends_at' => now()->addDays(30)->toDateString(),
        'price' => 1500,
        'amount_paid' => 1500,
        'remaining_class_credits' => 5,
        'status' => 'active',
    ]);

    // Member 1 books -> confirmed (credits 5 -> 4)
    Livewire::actingAs($user1)
        ->test(ClassBrowser::class)
        ->call('book', $schedule->id);

    $booking1 = Booking::where('member_id', $member1->id)->firstOrFail();
    expect($booking1->status)->toBe('confirmed')
        ->and($sub1->fresh()->remaining_class_credits)->toBe(4);

    // Member 2 books full class -> waitlisted #1 (credits stay 5)
    Livewire::actingAs($user2)
        ->test(ClassBrowser::class)
        ->call('book', $schedule->id);

    $booking2 = Booking::where('member_id', $member2->id)->firstOrFail();
    expect($booking2->status)->toBe('waitlisted')
        ->and($booking2->waitlist_position)->toBe(1)
        ->and($sub2->fresh()->remaining_class_credits)->toBe(5);

    // Member 1 cancels -> credit refunded (4 -> 5), Member 2 auto-promoted (credits 5 -> 4)
    Livewire::actingAs($user1)
        ->test(ClassBrowser::class)
        ->call('cancelBooking', $booking1->id);

    expect($booking1->fresh()->status)->toBe('cancelled')
        ->and($sub1->fresh()->remaining_class_credits)->toBe(5)
        ->and($booking2->fresh()->status)->toBe('confirmed')
        ->and($booking2->fresh()->waitlist_position)->toBeNull()
        ->and($sub2->fresh()->remaining_class_credits)->toBe(4);
});
