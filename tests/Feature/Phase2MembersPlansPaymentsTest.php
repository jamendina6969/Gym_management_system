<?php

use App\Livewire\Admin\Members\MemberIndex;
use App\Livewire\Admin\Members\MemberShow;
use App\Livewire\Admin\Payments\PaymentIndex;
use App\Livewire\Admin\Plans\PlanIndex;
use App\Livewire\Portal\MembershipManager;
use App\Models\Branch;
use App\Models\Member;
use App\Models\MemberInvitation;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\MemberInviteNotification;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('online self-signup requires waiver, assigns home branch, and creates pending member', function () {
    $branch = Branch::factory()->create(['allow_self_registration' => true]);

    // Missing waiver fails
    $this->post('/register', [
        'name' => 'Maria Santos',
        'email' => 'maria@example.com',
        'home_branch_id' => $branch->id,
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('accept_waiver');

    // Valid signup succeeds
    $this->post('/register', [
        'name' => 'Maria Santos',
        'email' => 'maria@example.com',
        'phone' => '+63 917 555 1234',
        'home_branch_id' => $branch->id,
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'accept_waiver' => '1',
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'maria@example.com')->firstOrFail();
    expect($user->member)->not->toBeNull()
        ->and($user->member->status)->toBe('pending')
        ->and($user->member->home_branch_id)->toBe($branch->id)
        ->and($user->member->waiver_accepted_at)->not->toBeNull()
        ->and($user->member->qr_token)->not->toBeEmpty();
});

test('walk-in member created by staff gets invite link and can set own password', function () {
    Notification::fake();

    $branch = Branch::factory()->create();
    $staff = User::factory()->create();
    $staff->assignRole('staff');
    $staff->branches()->attach($branch->id, ['is_primary' => true]);

    Livewire::actingAs($staff)
        ->test(MemberIndex::class)
        ->call('openCreateModal')
        ->set('name', 'Walkin Jose')
        ->set('email', 'jose@example.com')
        ->set('phone', '+63 918 111 2222')
        ->set('home_branch_id', $branch->id)
        ->set('approve_immediately', true)
        ->set('send_invite', true)
        ->call('createWalkInMember')
        ->assertHasNoErrors();

    $jose = User::where('email', 'jose@example.com')->firstOrFail();
    Notification::assertSentTo($jose, MemberInviteNotification::class);

    $invite = MemberInvitation::where('user_id', $jose->id)->firstOrFail();
    expect($jose->member->status)->toBe('active');

    // Member opens invite link and sets password
    $this->get(route('invite.show', $invite->token))->assertOk();

    $this->post(route('invite.accept', $invite->token), [
        'password' => 'MyNewPass123!',
        'password_confirmation' => 'MyNewPass123!',
        'accept_waiver' => '1',
    ])->assertRedirect(route('portal.home'));

    $invite->refresh();
    $jose->refresh();
    expect($invite->isAccepted())->toBeTrue()
        ->and($jose->email_verified_at)->not->toBeNull()
        ->and($jose->member->waiver_accepted_at)->not->toBeNull();
});

test('super admin can create membership plans via Livewire', function () {
    $branch = Branch::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    Livewire::actingAs($admin)
        ->test(PlanIndex::class)
        ->call('openCreateModal')
        ->set('name', 'VIP All Access')
        ->set('billing_cycle', 'monthly')
        ->set('duration_days', 30)
        ->set('price', '2500.00')
        ->set('access_scope', 'all_access')
        ->call('save')
        ->assertHasNoErrors();

    $plan = MembershipPlan::where('name', 'VIP All Access')->first();
    expect($plan)->not->toBeNull()
        ->and($plan->access_scope)->toBe('all_access')
        ->and((float) $plan->price)->toBe(2500.0);
});

test('staff can assign subscription with partial payment, record balance, and freeze/unfreeze', function () {
    $branch = Branch::factory()->create();
    $staff = User::factory()->create();
    $staff->assignRole('staff');
    $staff->branches()->attach($branch->id, ['is_primary' => true]);

    $plan = MembershipPlan::create([
        'branch_id' => $branch->id,
        'name' => 'Monthly Local',
        'billing_cycle' => 'monthly',
        'duration_days' => 30,
        'price' => 1500.00,
        'access_scope' => 'single_branch',
        'is_active' => true,
    ]);

    $memberUser = User::factory()->create();
    $memberUser->assignRole('member');
    $member = Member::create([
        'user_id' => $memberUser->id,
        'home_branch_id' => $branch->id,
        'status' => 'pending',
    ]);

    // Assign plan with partial payment of 500
    Livewire::actingAs($staff)
        ->test(MemberShow::class, ['member' => $member])
        ->call('openSubModal')
        ->set('selectedPlanId', $plan->id)
        ->set('starts_at', now()->toDateString())
        ->set('initial_payment', '500')
        ->set('payment_method', 'cash')
        ->call('assignSubscription')
        ->assertHasNoErrors();

    $sub = $member->subscriptions()->firstOrFail();
    $member->refresh();
    expect($sub->status)->toBe('active')
        ->and((float) $sub->amount_paid)->toBe(500.0)
        ->and($sub->balance())->toBe(1000.0)
        ->and($member->status)->toBe('active');

    // Record remaining 1000 balance
    Livewire::actingAs($staff)
        ->test(MemberShow::class, ['member' => $member])
        ->call('openPaymentModal', $sub->id)
        ->set('payment_amount', '1000')
        ->set('pay_method', 'gcash')
        ->set('pay_reference', 'GC-999')
        ->call('recordPayment')
        ->assertHasNoErrors();

    $sub->refresh();
    expect($sub->isFullyPaid())->toBeTrue();

    // Freeze & unfreeze
    Livewire::actingAs($staff)
        ->test(MemberShow::class, ['member' => $member])
        ->call('freezeSubscription', $sub->id);

    expect($sub->fresh()->status)->toBe('frozen')
        ->and($member->fresh()->status)->toBe('frozen');

    Livewire::actingAs($staff)
        ->test(MemberShow::class, ['member' => $member])
        ->call('unfreezeSubscription', $sub->id);

    expect($sub->fresh()->status)->toBe('active')
        ->and($member->fresh()->status)->toBe('active');
});

test('member submits manual payment in portal and staff verification activates subscription and allows PDF invoice', function () {
    $branch = Branch::factory()->create();
    $plan = MembershipPlan::create([
        'branch_id' => $branch->id,
        'name' => 'Monthly Pro',
        'billing_cycle' => 'monthly',
        'duration_days' => 30,
        'price' => 1800.00,
        'access_scope' => 'single_branch',
        'is_active' => true,
    ]);

    $memberUser = User::factory()->create();
    $memberUser->assignRole('member');
    $member = Member::create([
        'user_id' => $memberUser->id,
        'home_branch_id' => $branch->id,
        'status' => 'pending',
    ]);

    Livewire::actingAs($memberUser)
        ->test(MembershipManager::class)
        ->call('selectPlan', $plan->id)
        ->set('payment_method', 'gcash')
        ->set('reference_number', 'GCASH-888111')
        ->call('submitManualPayment')
        ->assertHasNoErrors();

    $payment = Payment::where('member_id', $member->id)->firstOrFail();
    expect($payment->status)->toBe('pending')
        ->and($payment->subscription->status)->toBe('pending')
        ->and($member->fresh()->status)->toBe('pending');

    // Staff verifies payment
    $staff = User::factory()->create();
    $staff->assignRole('staff');
    $staff->branches()->attach($branch->id, ['is_primary' => true]);

    Livewire::actingAs($staff)
        ->test(PaymentIndex::class)
        ->call('verifyPayment', $payment->id);

    expect($payment->fresh()->status)->toBe('paid')
        ->and($payment->subscription->fresh()->status)->toBe('active')
        ->and($member->fresh()->status)->toBe('active');

    // Owner and staff can download PDF invoice; another member gets 403
    $this->actingAs($memberUser)
        ->get(route('invoices.download', $payment))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs($staff)
        ->get(route('invoices.download', $payment))
        ->assertOk();

    $otherMember = User::factory()->create();
    $otherMember->assignRole('member');
    $this->actingAs($otherMember)
        ->get(route('invoices.download', $payment))
        ->assertForbidden();
});
