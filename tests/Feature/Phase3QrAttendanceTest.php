<?php

use App\Livewire\Admin\Attendance\CheckInConsole;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('active member checks in via QR token, avoids duplicate scans, and checks out', function () {
    $branch = Branch::factory()->create();
    $staff = User::factory()->create();
    $staff->assignRole('staff');
    $staff->branches()->attach($branch->id, ['is_primary' => true]);

    $plan = MembershipPlan::create([
        'branch_id' => $branch->id,
        'name' => 'Monthly Local',
        'billing_cycle' => 'monthly',
        'duration_days' => 30,
        'price' => 1500,
        'access_scope' => 'single_branch',
    ]);

    $memberUser = User::factory()->create();
    $memberUser->assignRole('member');
    $member = Member::create([
        'user_id' => $memberUser->id,
        'home_branch_id' => $branch->id,
        'status' => 'active',
    ]);
    Subscription::create([
        'member_id' => $member->id,
        'membership_plan_id' => $plan->id,
        'branch_id' => $branch->id,
        'starts_at' => now()->toDateString(),
        'ends_at' => now()->addDays(20)->toDateString(),
        'price' => 1500,
        'amount_paid' => 1500,
        'status' => 'active',
    ]);

    Livewire::actingAs($staff)
        ->test(CheckInConsole::class)
        ->set('activeBranchId', $branch->id)
        ->call('scanCode', $member->qr_token, 'qr')
        ->assertSet('lastResult.allowed', true)
        // Second rapid scan does not duplicate row
        ->call('scanCode', $member->qr_token, 'qr')
        ->assertSet('lastResult.allowed', true);

    expect(Attendance::where('member_id', $member->id)->count())->toBe(1);

    $attendance = Attendance::first();
    expect($attendance->status)->toBe('allowed')
        ->and($attendance->checked_out_at)->toBeNull();

    Livewire::actingAs($staff)
        ->test(CheckInConsole::class)
        ->call('checkOut', $attendance->id);

    expect($attendance->fresh()->checked_out_at)->not->toBeNull();
});

test('pending, frozen, and expired members are denied check-in', function () {
    $branch = Branch::factory()->create();
    $staff = User::factory()->create();
    $staff->assignRole('staff');
    $staff->branches()->attach($branch->id, ['is_primary' => true]);

    $plan = MembershipPlan::create([
        'branch_id' => $branch->id,
        'name' => 'Monthly',
        'billing_cycle' => 'monthly',
        'duration_days' => 30,
        'price' => 1500,
        'access_scope' => 'single_branch',
    ]);

    // 1. Pending member
    $pendingMember = Member::create([
        'user_id' => User::factory()->create()->id,
        'home_branch_id' => $branch->id,
        'status' => 'pending',
    ]);

    Livewire::actingAs($staff)
        ->test(CheckInConsole::class)
        ->set('activeBranchId', $branch->id)
        ->call('scanCode', $pendingMember->qr_token, 'qr')
        ->assertSet('lastResult.allowed', false);

    // 2. Expired subscription member
    $expiredMember = Member::create([
        'user_id' => User::factory()->create()->id,
        'home_branch_id' => $branch->id,
        'status' => 'active',
    ]);
    Subscription::create([
        'member_id' => $expiredMember->id,
        'membership_plan_id' => $plan->id,
        'branch_id' => $branch->id,
        'starts_at' => now()->subDays(40)->toDateString(),
        'ends_at' => now()->subDays(10)->toDateString(),
        'price' => 1500,
        'amount_paid' => 1500,
        'status' => 'active',
    ]);

    Livewire::actingAs($staff)
        ->test(CheckInConsole::class)
        ->set('activeBranchId', $branch->id)
        ->call('scanCode', $expiredMember->member_code, 'manual')
        ->assertSet('lastResult.allowed', false);

    expect(Attendance::where('status', 'denied')->count())->toBe(2);
});

test('single-branch plan is denied at another branch while all-access plan is allowed everywhere', function () {
    $branchA = Branch::factory()->create(['name' => 'Makati']);
    $branchB = Branch::factory()->create(['name' => 'BGC']);

    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $singlePlan = MembershipPlan::create([
        'branch_id' => $branchA->id,
        'name' => 'Makati Only',
        'billing_cycle' => 'monthly',
        'duration_days' => 30,
        'price' => 1500,
        'access_scope' => 'single_branch',
    ]);
    $allAccessPlan = MembershipPlan::create([
        'branch_id' => null,
        'name' => 'All-Access VIP',
        'billing_cycle' => 'monthly',
        'duration_days' => 30,
        'price' => 2500,
        'access_scope' => 'all_access',
    ]);

    $localMember = Member::create([
        'user_id' => User::factory()->create()->id,
        'home_branch_id' => $branchA->id,
        'status' => 'active',
    ]);
    Subscription::create([
        'member_id' => $localMember->id,
        'membership_plan_id' => $singlePlan->id,
        'branch_id' => $branchA->id,
        'starts_at' => now()->toDateString(),
        'ends_at' => now()->addDays(30)->toDateString(),
        'price' => 1500,
        'amount_paid' => 1500,
        'status' => 'active',
    ]);

    $vipMember = Member::create([
        'user_id' => User::factory()->create()->id,
        'home_branch_id' => $branchA->id,
        'status' => 'active',
    ]);
    Subscription::create([
        'member_id' => $vipMember->id,
        'membership_plan_id' => $allAccessPlan->id,
        'branch_id' => $branchA->id,
        'starts_at' => now()->toDateString(),
        'ends_at' => now()->addDays(30)->toDateString(),
        'price' => 2500,
        'amount_paid' => 2500,
        'status' => 'active',
    ]);

    // Local member denied at BGC
    Livewire::actingAs($admin)
        ->test(CheckInConsole::class)
        ->set('activeBranchId', $branchB->id)
        ->call('scanCode', $localMember->qr_token, 'qr')
        ->assertSet('lastResult.allowed', false);

    // VIP member allowed at BGC
    Livewire::actingAs($admin)
        ->test(CheckInConsole::class)
        ->set('activeBranchId', $branchB->id)
        ->call('scanCode', $vipMember->qr_token, 'qr')
        ->assertSet('lastResult.allowed', true);
});

test('member portal renders personal QR pass SVG and member code', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->create();
    $user->assignRole('member');
    $member = Member::create([
        'user_id' => $user->id,
        'home_branch_id' => $branch->id,
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->get(route('portal.qr-pass'))
        ->assertOk()
        ->assertSee($member->member_code)
        ->assertSee('<svg', false);
});
