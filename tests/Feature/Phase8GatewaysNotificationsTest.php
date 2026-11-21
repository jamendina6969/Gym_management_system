<?php

use App\Livewire\Admin\Settings\SettingsManager;
use App\Models\Branch;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Notifications\MembershipExpiringNotification;
use App\Notifications\PaymentReceiptNotification;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('super admin can configure encrypted payment gateways via Livewire', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    Livewire::actingAs($admin)
        ->test(SettingsManager::class)
        ->set('pm_enabled', true)
        ->set('pm_mode', 'test')
        ->set('pm_public_key', 'pk_test_123')
        ->set('pm_secret_key', 'sk_test_secret_abc')
        ->set('pm_webhook_secret', 'whsk_secret_xyz')
        ->call('saveGateways')
        ->assertHasNoErrors();

    $gw = PaymentGateway::where('driver', 'paymongo')->firstOrFail();
    expect($gw->is_enabled)->toBeTrue()
        ->and($gw->secret_key)->toBe('sk_test_secret_abc')
        ->and($gw->webhook_secret)->toBe('whsk_secret_xyz');

    // Ensure raw DB value is actually encrypted
    $raw = \Illuminate\Support\Facades\DB::table('payment_gateways')->where('id', $gw->id)->value('secret_key');
    expect($raw)->not->toBe('sk_test_secret_abc');
});

test('paymongo webhook verifies HMAC signature, activates subscription idempotently, and emails receipt', function () {
    Notification::fake();

    $branch = Branch::factory()->create();
    $plan = MembershipPlan::create([
        'branch_id' => $branch->id,
        'name' => 'Monthly Online',
        'billing_cycle' => 'monthly',
        'duration_days' => 30,
        'price' => 1500,
        'access_scope' => 'single_branch',
    ]);

    PaymentGateway::create([
        'branch_id' => null,
        'driver' => 'paymongo',
        'display_name' => 'PayMongo',
        'is_enabled' => true,
        'mode' => 'test',
        'secret_key' => 'sk_test_key',
        'webhook_secret' => 'whsk_test_secret',
    ]);

    $memberUser = User::factory()->create();
    $memberUser->assignRole('member');
    $member = Member::create([
        'user_id' => $memberUser->id,
        'home_branch_id' => $branch->id,
        'status' => 'pending',
    ]);
    $sub = Subscription::create([
        'member_id' => $member->id,
        'membership_plan_id' => $plan->id,
        'branch_id' => $branch->id,
        'starts_at' => now()->toDateString(),
        'ends_at' => now()->addDays(30)->toDateString(),
        'price' => 1500,
        'amount_paid' => 0,
        'status' => 'pending',
    ]);
    $payment = Payment::create([
        'branch_id' => $branch->id,
        'member_id' => $member->id,
        'subscription_id' => $sub->id,
        'amount' => 1500,
        'payment_method' => 'paymongo',
        'gateway' => 'paymongo',
        'gateway_reference' => 'cs_test_001',
        'status' => 'pending',
    ]);

    $payload = json_encode([
        'data' => [
            'id' => 'evt_pm_1001',
            'attributes' => [
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'id' => 'cs_test_001',
                    'attributes' => [
                        'metadata' => ['payment_id' => (string) $payment->id],
                    ],
                ],
            ],
        ],
    ]);

    // 1. Invalid signature -> 401
    $this->call('POST', '/webhooks/payments/paymongo', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_PAYMONGO_SIGNATURE' => 't=1700000000,te=badsignature,li=',
    ], $payload)->assertStatus(401);

    // 2. Valid HMAC signature -> 200 processed
    $ts = '1700000000';
    $sig = hash_hmac('sha256', $ts . '.' . $payload, 'whsk_test_secret');
    $this->call('POST', '/webhooks/payments/paymongo', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_PAYMONGO_SIGNATURE' => "t={$ts},te={$sig},li=",
    ], $payload)
        ->assertOk()
        ->assertJson(['status' => 'processed']);

    expect($payment->fresh()->status)->toBe('paid')
        ->and($sub->fresh()->status)->toBe('active')
        ->and($member->fresh()->status)->toBe('active');

    Notification::assertSentTo($memberUser, PaymentReceiptNotification::class);

    // 3. Replay same event_id -> idempotent already_processed
    $this->call('POST', '/webhooks/payments/paymongo', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_PAYMONGO_SIGNATURE' => "t={$ts},te={$sig},li=",
    ], $payload)
        ->assertOk()
        ->assertJson(['status' => 'already_processed']);

    expect(WebhookEvent::where('event_id', 'evt_pm_1001')->count())->toBe(1);
});

test('expiry reminder command expires overdue subscriptions and emails members expiring in N days', function () {
    Notification::fake();

    Setting::setValue('expiry_reminder_days', '7', null, 'notifications');
    $branch = Branch::factory()->create();
    $plan = MembershipPlan::create([
        'branch_id' => $branch->id,
        'name' => 'Monthly',
        'billing_cycle' => 'monthly',
        'duration_days' => 30,
        'price' => 1500,
        'access_scope' => 'single_branch',
    ]);

    // Expiring in exactly 7 days
    $userSoon = User::factory()->create();
    $memberSoon = Member::create(['user_id' => $userSoon->id, 'home_branch_id' => $branch->id, 'status' => 'active']);
    Subscription::create([
        'member_id' => $memberSoon->id,
        'membership_plan_id' => $plan->id,
        'branch_id' => $branch->id,
        'starts_at' => now()->subDays(23)->toDateString(),
        'ends_at' => now()->addDays(7)->toDateString(),
        'price' => 1500,
        'amount_paid' => 1500,
        'status' => 'active',
    ]);

    // Already overdue
    $userPast = User::factory()->create();
    $memberPast = Member::create(['user_id' => $userPast->id, 'home_branch_id' => $branch->id, 'status' => 'active']);
    $subPast = Subscription::create([
        'member_id' => $memberPast->id,
        'membership_plan_id' => $plan->id,
        'branch_id' => $branch->id,
        'starts_at' => now()->subDays(35)->toDateString(),
        'ends_at' => now()->subDays(5)->toDateString(),
        'price' => 1500,
        'amount_paid' => 1500,
        'status' => 'active',
    ]);

    $this->artisan('gym:send-expiry-reminders')->assertSuccessful();

    Notification::assertSentTo($userSoon, MembershipExpiringNotification::class);
    Notification::assertNotSentTo($userPast, MembershipExpiringNotification::class);

    expect($subPast->fresh()->status)->toBe('expired')
        ->and($memberPast->fresh()->status)->toBe('expired');
});
